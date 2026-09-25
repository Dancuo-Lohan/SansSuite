<?php
declare(strict_types=1);

namespace Services;

use Repositories\AttachmentRepository;
use RuntimeException;

final class AttachmentStorage
{
    private const MAX_FILE_SIZE = 5_242_880;

    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'txt' => ['text/plain'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
    ];

    public function __construct(
        private AttachmentRepository $attachments,
        private string $directory,
    ) {}

    /**
     * @param array<string, mixed> $files
     * @return list<string>
     */
    public function storeMany(int $applicationId, array $files): array
    {
        $normalized = $this->normalizeFiles($files);
        $errors = [];
        foreach ($normalized as $file) {
            try {
                $this->store($applicationId, $file);
            } catch (RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }
        return $errors;
    }

    /** @param array<string, mixed> $file */
    private function store(int $applicationId, array $file): void
    {
        $name = basename((string) ($file['name'] ?? ''));
        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Le fichier {$name} n'a pas pu être envoyé.");
        }
        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException("Le fichier {$name} doit peser moins de 5 Mo.");
        }

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED[$extension])) {
            throw new RuntimeException("Le format du fichier {$name} n'est pas accepté.");
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: 'application/octet-stream';
        if (!in_array($mime, self::ALLOWED[$extension], true)) {
            throw new RuntimeException("Le contenu du fichier {$name} ne correspond pas à son extension.");
        }

        $this->ensureDirectory();
        $storedName = bin2hex(random_bytes(24)) . '.' . $extension;
        $destination = $this->directory . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($tmpName, $destination)) {
            throw new RuntimeException("Le fichier {$name} n'a pas pu être enregistré.");
        }

        try {
            $this->attachments->create($applicationId, [
                'original_name' => $name,
                'stored_name' => $storedName,
                'mime_type' => $mime,
                'size' => $size,
            ]);
        } catch (\Throwable $exception) {
            @unlink($destination);
            throw $exception;
        }
    }

    public function path(string $storedName): ?string
    {
        if (basename($storedName) !== $storedName) {
            return null;
        }
        $path = $this->directory . DIRECTORY_SEPARATOR . $storedName;
        return is_file($path) ? $path : null;
    }

    public function delete(string $storedName): void
    {
        $path = $this->path($storedName);
        if ($path !== null) {
            @unlink($path);
        }
    }

    private function ensureDirectory(): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Le dossier des pièces jointes est indisponible.');
        }
    }

    /** @param array<string, mixed> $files @return list<array<string, mixed>> */
    private function normalizeFiles(array $files): array
    {
        if (!isset($files['name'])) {
            return [];
        }
        if (!is_array($files['name'])) {
            return [$files];
        }

        $normalized = [];
        foreach ($files['name'] as $index => $name) {
            if ((int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $normalized[] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }
        return $normalized;
    }
}
