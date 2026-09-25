<?php
declare(strict_types=1);

namespace Validation;

use Domain\ActivityType;

final class ActivityValidator
{
    /**
     * @param array<string, mixed> $input
     * @return array{data: array{type: string, occurred_at: string, due_at: string|null, note: string}, errors: array<string, string>}
     */
    public function validate(array $input): array
    {
        $type = trim((string) ($input['type'] ?? ''));
        $occurredAt = trim((string) ($input['occurred_at'] ?? ''));
        $dueAt = trim((string) ($input['due_at'] ?? ''));
        $note = trim((string) ($input['note'] ?? ''));
        $errors = [];

        if (!ActivityType::isValid($type) || $type === 'application') {
            $errors['type'] = "Choisissez un type d'événement.";
        }
        if (!$this->isDateTime($occurredAt)) {
            $errors['occurred_at'] = 'Indiquez une date valide.';
        }
        if ($dueAt !== '' && !$this->isDate($dueAt)) {
            $errors['due_at'] = "La date du rappel ou de l'événement n'est pas valide.";
        }

        return [
            'data' => [
                'type' => $type,
                'occurred_at' => isset($errors['occurred_at']) ? $occurredAt : $this->normalizeDateTime($occurredAt),
                'due_at' => $dueAt !== '' ? $dueAt : null,
                'note' => $note,
            ],
            'errors' => $errors,
        ];
    }

    private function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function isDateTime(string $value): bool
    {
        foreach (['Y-m-d\\TH:i', 'Y-m-d H:i:s', 'Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date !== false && $date->format($format) === $value) {
                return true;
            }
        }
        return false;
    }

    private function normalizeDateTime(string $value): string
    {
        return (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
    }
}
