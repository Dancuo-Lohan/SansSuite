<?php
declare(strict_types=1);

namespace Domain;

final class ApplicationKind
{
    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'listing' => 'Candidature sur annonce',
            'spontaneous' => 'Candidature spontanée',
        ];
    }

    public static function isValid(string $kind): bool
    {
        return array_key_exists($kind, self::labels());
    }

    public static function label(string $kind): string
    {
        return self::labels()[$kind] ?? $kind;
    }
}
