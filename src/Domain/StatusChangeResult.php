<?php
declare(strict_types=1);

namespace Domain;

final readonly class StatusChangeResult
{
    public const CHANGED = 'changed';
    public const UNCHANGED = 'unchanged';
    public const INVALID = 'invalid';

    /** @param array<string, string> $errors */
    private function __construct(
        public string $outcome,
        public array $errors = [],
    ) {}

    public static function changed(): self
    {
        return new self(self::CHANGED);
    }

    public static function unchanged(): self
    {
        return new self(self::UNCHANGED);
    }

    /** @param array<string, string> $errors */
    public static function invalid(array $errors): self
    {
        return new self(self::INVALID, $errors);
    }
}
