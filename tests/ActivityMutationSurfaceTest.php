<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ActivityMutationSurfaceTest extends TestCase
{
    public function testUpdateAndDeleteRoutesAreRegistered(): void
    {
        $routes = (string) file_get_contents(PROJECT_ROOT . '/public/routes.php');

        self::assertStringContainsString("'updateActivity'", $routes);
        self::assertStringContainsString("'deleteActivity'", $routes);
        self::assertStringContainsString('/activities/{activityId:', $routes);
    }

    public function testActivityMutationFormsContainCsrfProtectionAndDeleteConfirmation(): void
    {
        $view = (string) file_get_contents(
            PROJECT_ROOT . '/public/public_views/applications/show/activity.php'
        );

        self::assertGreaterThanOrEqual(2, substr_count($view, 'Csrf::input()'));
        self::assertStringContainsString('data-confirm=', $view);
    }
}
