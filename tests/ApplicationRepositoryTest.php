<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Domain\StatusChangeResult;
use Repositories\ActivityRepository;
use Repositories\ApplicationRepository;
use Services\ApplicationService;
use Services\CalendarService;
use Validation\ActivityValidator;
use Validation\ApplicationValidator;

final class ApplicationRepositoryTest extends TestCase
{
    private PDO $pdo;
    private ApplicationRepository $applications;
    private ActivityRepository $activities;
    private ApplicationService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $migrations = glob(PROJECT_ROOT . '/database/migrations/*.php') ?: [];
        sort($migrations, SORT_STRING);
        foreach ($migrations as $migrationFile) {
            $migration = require $migrationFile;
            $migration->up($this->pdo);
        }
        $this->applications = new ApplicationRepository($this->pdo);
        $this->activities = new ActivityRepository($this->pdo);
        $this->service = new ApplicationService(
            $this->pdo,
            $this->applications,
            $this->activities,
            new ApplicationValidator(),
            new ActivityValidator(),
        );
    }

    public function testApplicationCanBeCreatedSearchedAndFollowedUpSeveralTimes(): void
    {
        $id = $this->applications->create($this->data());
        $this->activities->create($id, 'follow_up', '2026-09-01 10:00:00');
        $this->activities->create($id, 'follow_up', '2026-09-08 10:00:00');

        $results = $this->applications->search(['q' => 'exemple', 'archive' => 'active']);

        self::assertCount(1, $results);
        self::assertSame(2, (int) $results[0]['follow_up_count']);
        self::assertSame(1, $this->applications->statusCounts()['waiting']);
        self::assertCount(1, $this->applications->waitingToReview(30, 5, '2026-10-01'));
        self::assertCount(2, $this->activities->recent());
    }

    public function testArchiveAndRestoreAreIndependentFromStatus(): void
    {
        $id = $this->applications->create($this->data());
        $this->applications->archive($id);
        self::assertNotNull($this->applications->find($id)['archived_at']);
        self::assertSame('waiting', $this->applications->find($id)['status']);

        $this->applications->restore($id);
        self::assertNull($this->applications->find($id)['archived_at']);
    }

    public function testRefusalDateAndCommentCanBeCorrectedWithoutCreatingADuplicate(): void
    {
        $id = $this->applications->create($this->data());

        self::assertSame(StatusChangeResult::CHANGED, $this->service->changeStatus($id, 'rejected', '2026-08-20', 'Retour reçu par e-mail.')->outcome);
        $firstRefusal = $this->activities->latestSystemOfType($id, 'rejection');
        self::assertNotNull($firstRefusal);
        self::assertSame('2026-08-20 00:00:00', $firstRefusal['occurred_at']);
        self::assertSame('Retour reçu par e-mail.', $firstRefusal['note']);

        self::assertSame(StatusChangeResult::CHANGED, $this->service->changeStatus($id, 'rejected', '2026-08-19', 'Date corrigée.')->outcome);
        $activities = array_values(array_filter(
            $this->activities->forApplication($id),
            static fn(array $activity): bool => $activity['type'] === 'rejection',
        ));

        self::assertCount(1, $activities);
        self::assertSame('2026-08-19 00:00:00', $activities[0]['occurred_at']);
        self::assertSame('Date corrigée.', $activities[0]['note']);
    }

    public function testUnchangedStatusDoesNotCreateAnActivity(): void
    {
        $id = $this->applications->create($this->data());

        $result = $this->service->changeStatus($id, 'waiting');

        self::assertSame(StatusChangeResult::UNCHANGED, $result->outcome);
        self::assertSame([], $this->activities->forApplication($id));
        self::assertSame('waiting', $this->applications->find($id)['status']);
    }

    public function testActualStatusChangeCreatesOneProtectedActivity(): void
    {
        $id = $this->applications->create($this->data());

        $result = $this->service->changeStatus($id, 'contact');
        $activities = $this->activities->forApplication($id);

        self::assertSame(StatusChangeResult::CHANGED, $result->outcome);
        self::assertCount(1, $activities);
        self::assertSame('system', $activities[0]['origin']);
        self::assertSame('response', $activities[0]['type']);
    }

    public function testUserActivityCanBeUpdatedAndDeleted(): void
    {
        $id = $this->applications->create($this->data());
        $activityId = $this->activities->create($id, 'note', '2026-09-10 09:00:00', 'Ancienne note');

        $errors = $this->service->updateActivity($id, $activityId, [
            'type' => 'follow_up',
            'occurred_at' => '2026-09-11T10:30',
            'due_at' => '2026-09-18',
            'note' => 'Note corrigée',
        ]);
        $updated = $this->activities->findForApplication($activityId, $id);

        self::assertSame([], $errors);
        self::assertSame('follow_up', $updated['type']);
        self::assertSame('2026-09-11 10:30:00', $updated['occurred_at']);
        self::assertSame('2026-09-18', $updated['due_at']);
        self::assertSame('Note corrigée', $updated['note']);
        self::assertCount(1, $this->activities->calendar('2026-09-01', '2026-09-30'));
        self::assertCount(1, $this->activities->recent());
        self::assertSame([], $this->service->deleteActivity($id, $activityId));
        self::assertNull($this->activities->findForApplication($activityId, $id));
        self::assertSame([], $this->activities->calendar('2026-09-01', '2026-09-30'));
        self::assertSame([], $this->activities->recent());
    }

    public function testProtectedOrForeignActivityCannotBeChanged(): void
    {
        $firstId = $this->applications->create($this->data());
        $secondId = $this->applications->create($this->data() + ['company' => 'Autre entreprise']);
        $systemActivityId = $this->activities->create($firstId, 'application', '2026-09-01 08:00:00', origin: 'system');
        $userActivityId = $this->activities->create($firstId, 'note', '2026-09-02 08:00:00', 'Privée');
        $input = ['type' => 'note', 'occurred_at' => '2026-09-03T08:00', 'due_at' => '', 'note' => 'Tentative'];

        self::assertNotSame([], $this->service->updateActivity($firstId, $systemActivityId, $input));
        self::assertNotSame([], $this->service->deleteActivity($firstId, $systemActivityId));
        self::assertNotSame([], $this->service->updateActivity($secondId, $userActivityId, $input));
        self::assertNotSame([], $this->service->deleteActivity($secondId, $userActivityId));
        self::assertNotNull($this->activities->findForApplication($systemActivityId, $firstId));
        self::assertNotNull($this->activities->findForApplication($userActivityId, $firstId));
    }

    public function testUpcomingCalendarEventsAppearBeforeHistoryOnTheSameDay(): void
    {
        $applicationId = $this->applications->create($this->data());
        $this->activities->create($applicationId, 'note', '2026-09-20 09:00:00', 'Historique');
        $this->activities->create(
            $applicationId,
            'follow_up',
            '2026-09-10 09:00:00',
            'À venir',
            '2026-09-20',
        );

        $events = (new CalendarService($this->activities))->events(
            '2026-09-01',
            '2026-09-30',
            '2026-09-15',
        );

        self::assertCount(2, $events['2026-09-20']);
        self::assertTrue($events['2026-09-20'][0]['calendar_is_upcoming']);
        self::assertSame('planned', $events['2026-09-20'][0]['calendar_kind']);
        self::assertSame('history', $events['2026-09-20'][1]['calendar_kind']);
    }

    public function testUpcomingTaskCanBeCompletedBeforeItsPlannedDate(): void
    {
        $applicationId = $this->applications->create($this->data());
        $activityId = $this->activities->create(
            $applicationId,
            'follow_up',
            '2026-09-10 09:00:00',
            'Relance planifiée',
            '2026-09-20',
        );

        self::assertSame(1, $this->activities->upcomingCount('2026-09-15'));
        self::assertCount(1, $this->activities->upcoming(6, '2026-09-15'));
        self::assertSame([], $this->service->completeActivity(
            $applicationId,
            $activityId,
            '2026-09-15 10:00:00',
        ));
        self::assertSame(0, $this->activities->upcomingCount('2026-09-15'));

        $events = (new CalendarService($this->activities))->events(
            '2026-09-01',
            '2026-09-30',
            '2026-09-15',
        );
        self::assertArrayNotHasKey('2026-09-20', $events);
        self::assertSame('completed', $events['2026-09-15'][0]['calendar_kind']);
    }

    /** @return array<string, string> */
    private function data(): array
    {
        return [
            'kind' => 'listing', 'company' => 'Entreprise exemple', 'location' => 'Paris',
            'position' => 'Développeur PHP', 'applied_at' => '2026-08-28', 'source_site' => '',
            'listing_url' => '', 'job_description' => '', 'cover_letter' => '', 'notes' => '', 'status' => 'waiting',
            'interview_preparation' => '', 'interview_questions' => '', 'interview_debrief' => '',
        ];
    }
}
