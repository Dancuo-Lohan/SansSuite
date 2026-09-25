<?php
declare(strict_types=1);

namespace Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Repositories\ActivityRepository;
use Repositories\ApplicationRepository;
use Support\ViewResponse;
use Domain\ApplicationStatus;

final class DashboardController
{
    public function __construct(
        private ApplicationRepository $applications,
        private ActivityRepository $activities,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        unset($request);
        $counts = $this->applications->statusCounts();
        $upcomingActivities = $this->activities->upcoming();
        $firstUpcomingDate = isset($upcomingActivities[0]['due_at'])
            ? substr((string) $upcomingActivities[0]['due_at'], 0, 10)
            : null;
        $upcomingUrl = $firstUpcomingDate !== null
            ? '/calendar?month=' . substr($firstUpcomingDate, 0, 7) . '&focus=' . $firstUpcomingDate
            : '/calendar';
        $quickStats = [
            ['label' => 'Total', 'count' => array_sum($counts), 'url' => '/applications'],
            ['label' => ApplicationStatus::label('waiting'), 'count' => $counts['waiting'] ?? 0, 'url' => '/applications?status=waiting'],
            ['label' => ApplicationStatus::label('rejected'), 'count' => $counts['rejected'] ?? 0, 'url' => '/applications?status=rejected'],
            ['label' => 'Tâches à venir', 'count' => $this->activities->upcomingCount(), 'url' => $upcomingUrl],
        ];

        return ViewResponse::render('home', [
            'metadata' => '<title>Vue d\'ensemble — Sans Suite</title><meta name="description" content="Suivi local de vos candidatures.">',
            'quickStats' => $quickStats,
            'reviewApplications' => $this->applications->waitingToReview(30, 3),
            'recentActivities' => $this->activities->recent(),
            'upcomingActivities' => $upcomingActivities,
        ]);
    }
}
