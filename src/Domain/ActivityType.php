<?php
declare(strict_types=1);

namespace Domain;

final class ActivityType
{
    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'application' => 'Candidature enregistrée',
            'follow_up' => 'Relance',
            'response' => 'Réponse reçue',
            'call' => 'Appel',
            'interview_scheduled' => 'Entretien prévu',
            'interview_completed' => 'Entretien passé',
            'offer' => 'Proposition reçue',
            'rejection' => 'Refus reçu',
            'note' => 'Note',
        ];
    }

    public static function isValid(string $type): bool
    {
        return array_key_exists($type, self::labels());
    }

    public static function label(string $type): string
    {
        return self::labels()[$type] ?? $type;
    }
}
