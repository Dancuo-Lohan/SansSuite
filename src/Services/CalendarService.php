<?php
declare(strict_types=1);

namespace Services;

use Repositories\ActivityRepository;

final class CalendarService
{
    public function __construct(private ActivityRepository $activities) {}

    /** @return array<string, list<array<string, mixed>>> */
    public function events(string $from, string $to, ?string $today = null): array
    {
        $events = [];
        $referenceDate = $today ?? date('Y-m-d');

        foreach ($this->activities->calendar($from, $to) as $activity) {
            $occurredDate = substr((string) $activity['occurred_at'], 0, 10);
            $dueDate = $activity['due_at'] !== null ? substr((string) $activity['due_at'], 0, 10) : null;
            $completedDate = $activity['completed_at'] !== null
                ? substr((string) $activity['completed_at'], 0, 10)
                : null;

            if ($dueDate !== null && $completedDate === null && $dueDate >= $from && $dueDate <= $to) {
                $events[$dueDate][] = $activity + [
                    'calendar_kind' => 'planned',
                    'calendar_is_upcoming' => $dueDate >= $referenceDate,
                ];
            }

            if ($completedDate !== null && $completedDate >= $from && $completedDate <= $to) {
                $events[$completedDate][] = $activity + [
                    'calendar_kind' => 'completed',
                    'calendar_is_upcoming' => false,
                ];
            }

            if ($occurredDate >= $from && $occurredDate <= $to && $occurredDate !== $dueDate) {
                $events[$occurredDate][] = $activity + [
                    'calendar_kind' => 'history',
                    'calendar_is_upcoming' => false,
                ];
            }
        }

        foreach ($events as &$dayEvents) {
            usort($dayEvents, static function (array $left, array $right): int {
                return self::priority($left) <=> self::priority($right);
            });
        }
        unset($dayEvents);

        return $events;
    }

    /** @param array<string, mixed> $event */
    private static function priority(array $event): int
    {
        if (($event['calendar_is_upcoming'] ?? false) === true) {
            return 0;
        }

        return ($event['calendar_kind'] ?? '') === 'planned' ? 1 : 2;
    }
}
