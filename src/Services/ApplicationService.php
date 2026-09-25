<?php
declare(strict_types=1);

namespace Services;

use Domain\ApplicationStatus;
use Domain\StatusChangeResult;
use PDO;
use Repositories\ActivityRepository;
use Repositories\ApplicationRepository;
use Validation\ActivityValidator;
use Validation\ApplicationValidator;

final class ApplicationService
{
    public function __construct(
        private PDO $pdo,
        private ApplicationRepository $applications,
        private ActivityRepository $activities,
        private ApplicationValidator $validator,
        private ActivityValidator $activityValidator,
    ) {}

    /**
     * @param array<string, mixed> $input
     * @return array{id: int|null, data: array<string, string>, errors: array<string, string>}
     */
    public function create(array $input): array
    {
        $validated = $this->validator->validate($input);
        if ($validated['errors'] !== []) {
            return ['id' => null] + $validated;
        }

        $this->pdo->beginTransaction();
        try {
            $id = $this->applications->create($validated['data']);
            $this->activities->create($id, 'application', $validated['data']['applied_at'], origin: 'system');
            $this->pdo->commit();
            return ['id' => $id] + $validated;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array{data: array<string, string>, errors: array<string, string>}
     */
    public function update(int $id, array $input): array
    {
        $validated = $this->validator->validate($input, true);
        if ($validated['errors'] === []) {
            $this->applications->update($id, $validated['data']);
        }
        return $validated;
    }

    /** @param array<string, mixed> $input @return array<string, string> */
    public function addActivity(int $applicationId, array $input): array
    {
        $validated = $this->activityValidator->validate($input);
        if ($validated['errors'] !== []) {
            return $validated['errors'];
        }

        $data = $validated['data'];
        $this->activities->create(
            $applicationId,
            $data['type'],
            $data['occurred_at'],
            $data['note'],
            $data['due_at'],
        );
        return [];
    }

    /** @param array<string, mixed> $input @return array<string, string> */
    public function updateActivity(int $applicationId, int $activityId, array $input): array
    {
        $activity = $this->activities->findForApplication($activityId, $applicationId);
        if ($activity === null) {
            return ['activity' => "Cet événement n'existe pas pour cette candidature."];
        }
        if (($activity['origin'] ?? 'user') !== 'user' || $activity['type'] === 'application') {
            return ['activity' => 'Cet événement automatique ne peut pas être modifié.'];
        }

        $validated = $this->activityValidator->validate($input);
        if ($validated['errors'] !== []) {
            return $validated['errors'];
        }

        if (!$this->activities->updateUserActivity($activityId, $applicationId, $validated['data'])) {
            return ['activity' => "L'événement n'a pas pu être modifié."];
        }
        return [];
    }

    /** @return array<string, string> */
    public function deleteActivity(int $applicationId, int $activityId): array
    {
        $activity = $this->activities->findForApplication($activityId, $applicationId);
        if ($activity === null) {
            return ['activity' => "Cet événement n'existe pas pour cette candidature."];
        }
        if (($activity['origin'] ?? 'user') !== 'user' || $activity['type'] === 'application') {
            return ['activity' => 'Cet événement automatique ne peut pas être supprimé.'];
        }
        if (!$this->activities->deleteUserActivity($activityId, $applicationId)) {
            return ['activity' => "L'événement n'a pas pu être supprimé."];
        }
        return [];
    }

    /** @return array<string, string> */
    public function completeActivity(
        int $applicationId,
        int $activityId,
        ?string $completedAt = null,
    ): array {
        $activity = $this->activities->findForApplication($activityId, $applicationId);
        if ($activity === null) {
            return ['activity' => "Cette tâche n'existe pas pour cette candidature."];
        }
        if ($activity['due_at'] === null) {
            return ['activity' => "Cet événement n'a pas de rappel à effectuer."];
        }
        if ($activity['completed_at'] !== null) {
            return [];
        }
        if (!$this->activities->complete($activityId, $applicationId, $completedAt)) {
            return ['activity' => "La tâche n'a pas pu être marquée comme effectuée."];
        }
        return [];
    }

    public function changeStatus(
        int $applicationId,
        string $status,
        ?string $rejectionDate = null,
        ?string $rejectionNote = null,
    ): StatusChangeResult
    {
        if (!ApplicationStatus::isValid($status)) {
            return StatusChangeResult::invalid(['status' => "Ce statut n'est pas valide."]);
        }

        $application = $this->applications->find($applicationId);
        if ($application === null) {
            return StatusChangeResult::invalid(['application' => "Cette candidature n'existe pas."]);
        }
        $rejectionDate = trim((string) $rejectionDate);
        $rejectionNote = trim((string) $rejectionNote);
        if ($status === 'rejected' && !$this->isDate($rejectionDate)) {
            return StatusChangeResult::invalid(['rejection_date' => 'Indiquez une date de refus valide.']);
        }

        $currentStatus = (string) $application['status'];
        if ($status === $currentStatus && $status !== 'rejected') {
            return StatusChangeResult::unchanged();
        }

        if ($status === 'rejected' && $status === $currentStatus) {
            $existingRejection = $this->activities->latestSystemOfType($applicationId, 'rejection');
            $occurredAt = $rejectionDate . ' 00:00:00';
            if (
                $existingRejection !== null
                && (string) $existingRejection['occurred_at'] === $occurredAt
                && (string) $existingRejection['note'] === $rejectionNote
            ) {
                return StatusChangeResult::unchanged();
            }
        }

        $this->pdo->beginTransaction();
        try {
            if ($status !== $currentStatus) {
                $this->applications->updateStatus($applicationId, $status);
            }
            if ($status === 'rejected') {
                $this->upsertSystemRejection($applicationId, $rejectionDate, $rejectionNote);
            } else {
                $event = match ($status) {
                    'contact' => 'response',
                    'interview' => 'interview_scheduled',
                    'offer', 'accepted' => 'offer',
                    default => 'note',
                };
                $this->activities->create(
                    $applicationId,
                    $event,
                    date('Y-m-d H:i:s'),
                    'Statut : ' . ApplicationStatus::label($status),
                    origin: 'system',
                );
            }
            $this->pdo->commit();
            return StatusChangeResult::changed();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function upsertSystemRejection(int $applicationId, string $date, string $note): void
    {
        $occurredAt = $date . ' 00:00:00';
        $existingRejection = $this->activities->latestSystemOfType($applicationId, 'rejection');
        if ($existingRejection === null) {
            $this->activities->create($applicationId, 'rejection', $occurredAt, $note, origin: 'system');
            return;
        }
        $this->activities->updateDetails((int) $existingRejection['id'], $occurredAt, $note);
    }

    private function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

}
