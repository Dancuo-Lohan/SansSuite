<?php
declare(strict_types=1);

use Domain\ApplicationAge;
use PHPUnit\Framework\TestCase;

final class ApplicationAgeTest extends TestCase
{
    public function testAgeLabelsTodayAndElapsedDays(): void
    {
        $today = new DateTimeImmutable('2026-08-28');

        self::assertSame("Aujourd'hui", ApplicationAge::label('2026-08-28', $today));
        self::assertSame('Depuis 1 jour', ApplicationAge::label('2026-08-27', $today));
        self::assertSame('Depuis 12 jours', ApplicationAge::label('2026-08-16', $today));
    }

    public function testFutureDateIsHandledDefensively(): void
    {
        self::assertSame('Dans 2 jours', ApplicationAge::label('2026-08-30', new DateTimeImmutable('2026-08-28')));
    }
}
