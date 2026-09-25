<?php
declare(strict_types=1);

namespace Repositories;

use PDO;

final class ApplicationRepository
{
    public function __construct(private PDO $pdo) {}

    /** @param array<string, string> $data */
    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO applications (
    kind, company, location, position, applied_at, source_site, listing_url,
    job_description, cover_letter, notes, status, interview_preparation, interview_questions,
    interview_debrief, created_at, updated_at
) VALUES (
    :kind, :company, :location, :position, :applied_at, :source_site, :listing_url,
    :job_description, :cover_letter, :notes, :status, :interview_preparation, :interview_questions,
    :interview_debrief, :created_at, :updated_at
)
SQL);
        $statement->execute($data + ['created_at' => $now, 'updated_at' => $now]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, string> $data */
    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
UPDATE applications SET
    kind = :kind, company = :company, location = :location, position = :position,
    applied_at = :applied_at, source_site = :source_site, listing_url = :listing_url,
    job_description = :job_description, cover_letter = :cover_letter, notes = :notes, status = :status,
    interview_preparation = :interview_preparation,
    interview_questions = :interview_questions,
    interview_debrief = :interview_debrief,
    updated_at = :updated_at
WHERE id = :id
SQL);
        $statement->execute($data + ['id' => $id, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT a.*,
       (SELECT COUNT(*) FROM activities f WHERE f.application_id = a.id AND f.type = 'follow_up') AS follow_up_count,
       (SELECT MAX(x.occurred_at) FROM activities x WHERE x.application_id = a.id) AS last_activity_at
FROM applications a
WHERE a.id = :id
SQL);
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, string> $filters
     * @return list<array<string, mixed>>
     */
    public function search(array $filters): array
    {
        $where = [];
        $parameters = [];

        $archive = $filters['archive'] ?? 'active';
        if ($archive === 'archived') {
            $where[] = 'a.archived_at IS NOT NULL';
        } elseif ($archive !== 'all') {
            $where[] = 'a.archived_at IS NULL';
        }

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(a.company LIKE :q OR a.position LIKE :q OR a.location LIKE :q OR a.job_description LIKE :q OR a.notes LIKE :q)';
            $parameters['q'] = '%' . $filters['q'] . '%';
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'a.status = :status';
            $parameters['status'] = $filters['status'];
        }
        if (($filters['kind'] ?? '') !== '') {
            $where[] = 'a.kind = :kind';
            $parameters['kind'] = $filters['kind'];
        }
        if (($filters['from'] ?? '') !== '') {
            $where[] = 'a.applied_at >= :date_from';
            $parameters['date_from'] = $filters['from'];
        }
        if (($filters['to'] ?? '') !== '') {
            $where[] = 'a.applied_at <= :date_to';
            $parameters['date_to'] = $filters['to'];
        }
        if (($filters['follow_up'] ?? '') === 'yes') {
            $where[] = "EXISTS (SELECT 1 FROM activities fu WHERE fu.application_id = a.id AND fu.type = 'follow_up')";
        } elseif (($filters['follow_up'] ?? '') === 'no') {
            $where[] = "NOT EXISTS (SELECT 1 FROM activities fu WHERE fu.application_id = a.id AND fu.type = 'follow_up')";
        }

        $sorts = [
            'recent' => 'a.applied_at DESC, a.id DESC',
            'oldest' => 'a.applied_at ASC, a.id ASC',
            'company' => 'a.company COLLATE NOCASE ASC, a.applied_at DESC',
            'activity' => 'last_activity_at DESC, a.applied_at DESC',
        ];
        $orderBy = $sorts[$filters['sort'] ?? 'recent'] ?? $sorts['recent'];
        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $statement = $this->pdo->prepare(<<<SQL
SELECT a.*,
       (SELECT COUNT(*) FROM activities f WHERE f.application_id = a.id AND f.type = 'follow_up') AS follow_up_count,
       COALESCE((SELECT MAX(x.occurred_at) FROM activities x WHERE x.application_id = a.id), a.applied_at) AS last_activity_at
FROM applications a
{$whereSql}
ORDER BY {$orderBy}
SQL);
        $statement->execute($parameters);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string, int> */
    public function statusCounts(): array
    {
        $statement = $this->pdo->query(<<<'SQL'
SELECT status, COUNT(*) AS total
FROM applications
WHERE archived_at IS NULL
GROUP BY status
SQL);

        $counts = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    /** @return list<array<string, mixed>> */
    public function waitingToReview(int $days = 30, int $limit = 5, ?string $today = null): array
    {
        $reference = new \DateTimeImmutable($today ?? 'today');
        $cutoff = $reference->modify('-' . max(1, $days) . ' days')->format('Y-m-d');

        $statement = $this->pdo->prepare(<<<'SQL'
SELECT *
FROM applications
WHERE archived_at IS NULL
  AND status = 'waiting'
  AND applied_at <= :cutoff
ORDER BY applied_at ASC, id ASC
LIMIT :limit
SQL);
        $statement->bindValue('cutoff', $cutoff);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->pdo->prepare('UPDATE applications SET status = :status, updated_at = :updated_at WHERE id = :id');
        $statement->execute(['status' => $status, 'updated_at' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    public function archive(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE applications SET archived_at = :now, updated_at = :now WHERE id = :id');
        $statement->execute(['now' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    public function restore(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE applications SET archived_at = NULL, updated_at = :now WHERE id = :id');
        $statement->execute(['now' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM applications WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
