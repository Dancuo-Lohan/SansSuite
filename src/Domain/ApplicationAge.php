<?php
declare(strict_types=1);

namespace Domain;

final class ApplicationAge
{
    public static function label(string $appliedAt, ?\DateTimeImmutable $today = null): string
    {
        $applicationDate = new \DateTimeImmutable($appliedAt);
        $referenceDate = $today ?? new \DateTimeImmutable('today');
        $days = (int) $referenceDate->setTime(0, 0)->diff($applicationDate->setTime(0, 0))->format('%r%a');

        if ($days === 0) {
            return "Aujourd'hui";
        }

        if ($days > 0) {
            return 'Dans ' . $days . ' jour' . ($days > 1 ? 's' : '');
        }

        $elapsed = abs($days);
        return 'Depuis ' . $elapsed . ' jour' . ($elapsed > 1 ? 's' : '');
    }
}
