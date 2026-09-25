<?php
declare(strict_types=1);

namespace Support;

final class Flash
{
    public static function success(string $message): void
    {
        $_SESSION['_flash_success'] = $message;
    }

    public static function error(string $message): void
    {
        $_SESSION['_flash_error'] = $message;
    }

    /** @return array{success: string|null, error: string|null} */
    public static function consume(): array
    {
        $messages = [
            'success' => isset($_SESSION['_flash_success']) ? (string) $_SESSION['_flash_success'] : null,
            'error' => isset($_SESSION['_flash_error']) ? (string) $_SESSION['_flash_error'] : null,
        ];
        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);
        return $messages;
    }
}
