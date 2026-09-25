<?php
declare(strict_types=1);

namespace Repositories;

use PDO;

final class ActivityRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(
        int $applicationId,
        string $type,
        string $occurredAt,
        string $note = '',
        ?string $dueAt = null,
        string $origin = 'user',
    ): int
    {
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO activities (application_id, type, occurred_at, due_at, note, origin, created_at)
VALUES (:application_id, :type, :occurred_at, :due_at, :note, :origin, :created_at)
SQL);
        $statement->execute([
            'application_id' => $applicationId,
            'type' => $type,
            'occurred_at' => $occurredAt,
            'due_at' => $dueAt !== '' ? $dueAt : null,
            'note' => $note,
            'origin' => $origin,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function forApplication(int $applicationId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM activities WHERE application_id = :id ORDER BY occurred_at DESC, id DESC');
        $statement->execute(['id' => $applicationId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string, mixed>|null */
    public function latestOfType(int $applicationId, string $type): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT *
FROM activities
WHERE application_id = :application_id AND type = :type
ORDER BY occurred_at DESC, id DESC
LIMIT 1
SQL);
        $statement->execute(['application_id' => $applicationId, 'type' => $type]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function latestSystemOfType(int $applicationId, string $type): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT *
FROM activities
WHERE application_id = :application_id AND type = :type AND origin = 'system'
ORDER BY occurred_at DESC, id DESC
LIMIT 1
SQL);
        $statement->execute(['application_id' => $applicationId, 'type' => $type]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findForApplication(int $id, int $applicationId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM activities WHERE id = :id AND application_id = :application_id'
        );
        $statement->execute(['id' => $id, 'application_id' => $applicationId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /** @param array{type: string, occurred_at: string, due_at: string|null, note: string} $data */
    public function updateUserActivity(int $id, int $applicationId, array $data): bool
    {
        $statement = $this->pdo->prepare(<<<'SQL'
UPDATE activities
SET type = :type,
    occurred_at = :occurred_at,
    due_at = :due_at,
    note = :note,
    completed_at = CASE WHEN :due_at IS NULL THEN NULL ELSE completed_at END
WHERE id = :id AND application_id = :application_id AND origin = 'user' AND type <> 'application'
SQL);
        $statement->execute($data + ['id' => $id, 'application_id' => $applicationId]);
        return $statement->rowCount() === 1;
    }

    public function deleteUserActivity(int $id, int $applicationId): bool
    {
        $statement = $this->pdo->prepare(<<<'SQL'
DELETE FROM activities
WHERE id = :id AND application_id = :application_id AND origin = 'user' AND type <> 'application'
SQL);
        $statement->execute(['id' => $id, 'application_id' => $applicationId]);
        return $statement->rowCount() === 1;
    }

    public function updateDetails(int $id, string $occurredAt, string $note): void
    {
        $statement = $this->pdo->prepare('UPDATE activities SET occurred_at = :occurred_at, note = :note WHERE id = :id');
        $statement->execute(['occurred_at' => $occurredAt, 'note' => $note, 'id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function upcoming(int $limit = 6, ?string $today = null): array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT activities.*, applications.company, applications.position
FROM activities
JOIN applications ON applications.id = activities.application_id
WHERE activities.due_at IS NOT NULL
  AND activities.completed_at IS NULL
  AND activities.due_at >= :today
  AND applications.archived_at IS NULL
ORDER BY activities.due_at ASC
LIMIT :limit
SQL);
        $statement->bindValue('today', $today ?? date('Y-m-d'));
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function upcomingCount(?string $today = null): int
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT COUNT(*)
FROM activities
JOIN applications ON applications.id = activities.application_id
WHERE activities.due_at IS NOT NULL
  AND activities.completed_at IS NULL
  AND activities.due_at >= :today
  AND applications.archived_at IS NULL
SQL);
        $statement->execute(['today' => $today ?? date('Y-m-d')]);
        return (int) $statement->fetchColumn();
    }

    public function complete(int $id, int $applicationId, ?string $completedAt = null): bool
    {
        $statement = $this->pdo->prepare(<<<'SQL'
UPDATE activities
SET completed_at = :completed_at
WHERE id = :id
  AND application_id = :application_id
  AND due_at IS NOT NULL
  AND completed_at IS NULL
SQL);
        $statement->execute([
            'completed_at' => $completedAt ?? date('Y-m-d H:i:s'),
            'id' => $id,
            'application_id' => $applicationId,
        ]);
        return $statement->rowCount() === 1;
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 8): array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT activities.*, applications.company, applications.position
FROM activities
JOIN applications ON applications.id = activities.application_id
WHERE applications.archived_at IS NULL
ORDER BY activities.created_at DESC, activities.id DESC
LIMIT :limit
SQL);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function calendar(string $from, string $to): array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT activities.*, applications.company, applications.position
FROM activities
JOIN applications ON applications.id = activities.application_id
WHERE applications.archived_at IS NULL
  AND (
      date(activities.occurred_at) BETWEEN :date_from AND :date_to
      OR date(activities.due_at) BETWEEN :date_from AND :date_to
      OR date(activities.completed_at) BETWEEN :date_from AND :date_to
  )
ORDER BY activities.occurred_at, activities.id
SQL);
        $statement->execute(['date_from' => $from, 'date_to' => $to]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
