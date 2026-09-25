<?php
declare(strict_types=1);

namespace Support;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final class ViewResponse
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], int $httpStatus = 200): ResponseInterface
    {
        $viewFile = PROJECT_ROOT . '/public/public_views/' . $view . '/index.php';
        if (!is_file($viewFile)) {
            return new Response(500, [], 'Vue introuvable');
        }

        // These shared variables are available to the header, view, and footer.
        $__corianderRequestedView = $view;
        $flash = Flash::consume();

        // Controller data becomes local variables in the included view files.
        extract($data, EXTR_SKIP);

        ob_start();
        require PROJECT_ROOT . '/public/public_views/header.php';
        require $viewFile;
        require PROJECT_ROOT . '/public/public_views/footer.php';

        return new Response($httpStatus, ['Content-Type' => 'text/html; charset=UTF-8'], (string) ob_get_clean());
    }

    public static function redirect(string $location, int $status = 303): ResponseInterface
    {
        return new Response($status, ['Location' => $location]);
    }
}
