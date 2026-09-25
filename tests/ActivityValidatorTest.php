<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Validation\ActivityValidator;

final class ActivityValidatorTest extends TestCase
{
    public function testValidActivityIsNormalized(): void
    {
        $result = (new ActivityValidator())->validate([
            'type' => 'note',
            'occurred_at' => '2026-09-23T14:05',
            'due_at' => '2026-09-30',
            'note' => '  À compléter  ',
        ]);

        self::assertSame([], $result['errors']);
        self::assertSame('2026-09-23 14:05:00', $result['data']['occurred_at']);
        self::assertSame('2026-09-30', $result['data']['due_at']);
        self::assertSame('À compléter', $result['data']['note']);
    }

    public function testInternalTypeAndInvalidDatesAreRejected(): void
    {
        $result = (new ActivityValidator())->validate([
            'type' => 'application',
            'occurred_at' => 'date inconnue',
            'due_at' => '2026-02-31',
            'note' => '',
        ]);

        self::assertArrayHasKey('type', $result['errors']);
        self::assertArrayHasKey('occurred_at', $result['errors']);
        self::assertArrayHasKey('due_at', $result['errors']);
    }
}
