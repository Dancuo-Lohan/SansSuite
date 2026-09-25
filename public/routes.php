<?php
declare(strict_types=1);

use Controllers\ApplicationController;
use Controllers\CalendarController;
use Controllers\DashboardController;
use Controllers\ExportController;
use CorianderCore\Core\Container\Container;
use CorianderCore\Core\Database\DatabaseHandler;
use CorianderCore\Core\Router\Router;
use Repositories\ActivityRepository;
use Repositories\ApplicationRepository;
use Repositories\AttachmentRepository;
use Repositories\ContactRepository;
use Services\ApplicationService;
use Services\AttachmentStorage;
use Services\CalendarService;
use Services\ContactService;
use Services\CsvExportService;
use Support\Database;
use Validation\ActivityValidator;
use Validation\ApplicationValidator;

/** @var Router $router */
/** @var Container $container */

// Dépendances partagées par les différentes pages.
$pdo = Database::fromHandler($container->get(DatabaseHandler::class));
$applications = new ApplicationRepository($pdo);
$activities = new ActivityRepository($pdo);
$contacts = new ContactRepository($pdo);
$attachments = new AttachmentRepository($pdo);
$applicationService = new ApplicationService(
    $pdo,
    $applications,
    $activities,
    new ApplicationValidator(),
    new ActivityValidator(),
);
$attachmentStorage = new AttachmentStorage($attachments, PROJECT_ROOT . '/storage/attachments');

$dashboardController = new DashboardController($applications, $activities);
$applicationController = new ApplicationController(
    $applications,
    $activities,
    $contacts,
    $attachments,
    $applicationService,
    new ContactService($contacts),
    $attachmentStorage,
);
$calendarController = new CalendarController(new CalendarService($activities));
$exportController = new ExportController($applications, new CsvExportService());

// Vue d'ensemble.
$router->get('home', [$dashboardController, 'index']);

// Candidatures : liste, création et fiche.
$router->get('applications', [$applicationController, 'index']);
$router->get('applications/create', [$applicationController, 'create']);
$router->post('applications', [$applicationController, 'store']);
$router->get('applications/{id:\\d+}', [$applicationController, 'show']);
$router->get('applications/{id:\\d+}/edit', [$applicationController, 'edit']);
$router->post('applications/{id:\\d+}/update', [$applicationController, 'update']);

// Suivi d'une candidature.
$router->post('applications/{id:\\d+}/status', [$applicationController, 'changeStatus']);
$router->post('applications/{id:\\d+}/activities', [$applicationController, 'addActivity']);
$router->post('applications/{id:\\d+}/activities/{activityId:\\d+}/update', [$applicationController, 'updateActivity']);
$router->post('applications/{id:\\d+}/activities/{activityId:\\d+}/complete', [$applicationController, 'completeActivity']);
$router->post('applications/{id:\\d+}/activities/{activityId:\\d+}/delete', [$applicationController, 'deleteActivity']);
$router->post('applications/{id:\\d+}/archive', [$applicationController, 'archive']);
$router->post('applications/{id:\\d+}/restore', [$applicationController, 'restore']);
$router->post('applications/{id:\\d+}/delete', [$applicationController, 'delete']);

// Pièces jointes et contacts.
$router->post('applications/{id:\\d+}/attachments', [$applicationController, 'upload']);
$router->get('attachments/{attachmentId:\\d+}/download', [$applicationController, 'download']);
$router->post('applications/{id:\\d+}/attachments/{attachmentId:\\d+}/delete', [$applicationController, 'deleteAttachment']);
$router->post('applications/{id:\\d+}/contacts', [$applicationController, 'addContact']);
$router->post('applications/{id:\\d+}/contacts/{contactId:\\d+}/delete', [$applicationController, 'deleteContact']);

// Calendrier et export.
$router->get('calendar', [$calendarController, 'index']);
$router->get('export/csv', [$exportController, 'csv']);
