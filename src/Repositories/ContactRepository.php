<?php
declare(strict_types=1);

namespace Repositories;

use PDO;

final class ContactRepository
{
    public function __construct(private PDO $pdo) {}

    /** @param array<string, string> $data */
    public function create(int $applicationId, array $data): int
    {
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO contacts (application_id, name, role, email, phone, linkedin_url, created_at)
VALUES (:application_id, :name, :role, :email, :phone, :linkedin_url, :created_at)
SQL);
        $statement->execute($data + ['application_id' => $applicationId, 'created_at' => date('Y-m-d H:i:s')]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function forApplication(int $applicationId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM contacts WHERE application_id = :id ORDER BY name COLLATE NOCASE');
        $statement->execute(['id' => $applicationId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deleteForApplication(int $contactId, int $applicationId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM contacts WHERE id = :id AND application_id = :application_id');
        $statement->execute(['id' => $contactId, 'application_id' => $applicationId]);
    }
}
