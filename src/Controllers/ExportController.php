<?php
declare(strict_types=1);

namespace Controllers;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Repositories\ApplicationRepository;
use Services\CsvExportService;

final class ExportController
{
    public function __construct(
        private ApplicationRepository $applications,
        private CsvExportService $exporter,
    ) {}

    public function csv(ServerRequestInterface $request): ResponseInterface
    {
        $filters = array_map(static fn(mixed $value): string => trim((string) $value), $request->getQueryParams());
        $csv = $this->exporter->build($this->applications->search($filters));

        return new Response(200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sans-suite-' . date('Y-m-d') . '.csv"',
            'Content-Length' => (string) strlen($csv),
        ], $csv);
    }
}
