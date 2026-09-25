<?php
declare(strict_types=1);

namespace Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Services\CalendarService;
use Support\ViewResponse;

final class CalendarController
{
    public function __construct(private CalendarService $calendar) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $month = (string) ($query['month'] ?? date('Y-m'));
        $focusDate = trim((string) ($query['focus'] ?? ''));
        $parsedFocusDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $focusDate);
        if ($parsedFocusDate === false || $parsedFocusDate->format('Y-m-d') !== $focusDate) {
            $focusDate = null;
        }
        $firstDay = \DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01');
        if ($firstDay === false) {
            $firstDay = new \DateTimeImmutable('first day of this month');
        }
        $lastDay = $firstDay->modify('last day of this month');
        $calendarStart = $firstDay->modify('monday this week');
        $calendarEnd = $lastDay->modify('sunday this week');
        if ((int) $firstDay->format('N') === 1) {
            $calendarStart = $firstDay;
        }
        if ((int) $lastDay->format('N') === 7) {
            $calendarEnd = $lastDay;
        }

        return ViewResponse::render('calendar', [
            'metadata' => '<title>Calendrier — Sans Suite</title><meta name="description" content="Historique et événements à venir des candidatures.">',
            'firstDay' => $firstDay,
            'calendarStart' => $calendarStart,
            'calendarEnd' => $calendarEnd,
            'focusDate' => $focusDate,
            'events' => $this->calendar->events(
                $calendarStart->format('Y-m-d'),
                $calendarEnd->format('Y-m-d'),
            ),
        ]);
    }
}
