<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Services\CsvExportService;

final class CsvExportServiceTest extends TestCase
{
    public function testFormulaLikeCellsAreNeutralized(): void
    {
        $csv = (new CsvExportService())->build([[
            'company' => '=DANGEREUX()', 'position' => 'Poste', 'location' => 'Paris',
            'kind' => 'listing', 'applied_at' => '2026-08-28', 'status' => 'waiting',
            'source_site' => '', 'listing_url' => '', 'follow_up_count' => 0, 'notes' => '',
            'job_description' => '',
        ]]);

        self::assertStringContainsString("'=DANGEREUX()", $csv);
        self::assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }
}
