<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Validation\ApplicationValidator;

final class ApplicationValidatorTest extends TestCase
{
    public function testMinimalApplicationIsValidAndWaitingByDefault(): void
    {
        $result = (new ApplicationValidator())->validate([
            'kind' => 'listing',
            'company' => 'Entreprise exemple',
            'location' => 'Paris',
            'position' => 'Développeur PHP',
            'applied_at' => '2026-08-28',
        ]);

        self::assertSame([], $result['errors']);
        self::assertSame('waiting', $result['data']['status']);
        self::assertSame('', $result['data']['cover_letter']);
    }

    public function testRequiredFieldsAndUrlsAreValidated(): void
    {
        $result = (new ApplicationValidator())->validate([
            'kind' => 'unknown',
            'company' => '',
            'position' => '',
            'applied_at' => '28/08/2026',
            'listing_url' => 'pas une url',
        ]);

        self::assertArrayHasKey('kind', $result['errors']);
        self::assertArrayHasKey('company', $result['errors']);
        self::assertArrayHasKey('position', $result['errors']);
        self::assertArrayHasKey('location', $result['errors']);
        self::assertArrayHasKey('applied_at', $result['errors']);
        self::assertArrayHasKey('listing_url', $result['errors']);
    }
}
