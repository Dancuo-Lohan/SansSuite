<?php
declare(strict_types=1);

namespace Repositories;

use PDO;

final class AttachmentRepository
{
    public function __construct(private PDO $pdo) {}

    /** @param array{original_name: string, stored_name: string, mime_type: string, size: int} $data */
    public function create(int $applicationId, array $data): int
    {
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO attachments (application_id, original_name, stored_name, mime_type, size, created_at)
VALUES (:application_id, :original_name, :stored_name, :mime_type, :size, :created_at)
SQL);
        $statement->execute($data + ['application_id' => $applicationId, 'created_at' => date('Y-m-d H:i:s')]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function forApplication(int $applicationId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM attachments WHERE application_id = :id ORDER BY created_at DESC');
        $statement->execute(['id' => $applicationId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM attachments WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public function forApplicationBeforeDelete(int $applicationId): array
    {
        return $this->forApplication($applicationId);
    }

    public function deleteForApplication(int $id, int $applicationId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM attachments WHERE id = :id AND application_id = :application_id');
        $statement->execute(['id' => $id, 'application_id' => $applicationId]);
    }
}
