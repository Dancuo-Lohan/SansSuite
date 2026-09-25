<?php
declare(strict_types=1);

namespace Domain;

final class ApplicationStatus
{
    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'waiting' => 'En attente',
            'contact' => 'Échange en cours',
            'interview' => 'Entretien',
            'offer' => 'Proposition reçue',
            'accepted' => 'Acceptée',
            'rejected' => 'Refusée',
            'no_response' => 'Sans réponse',
        ];
    }

    public static function isValid(string $status): bool
    {
        return array_key_exists($status, self::labels());
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }

    public static function hasProgressed(string $status): bool
    {
        return $status !== 'waiting';
    }
}
