<?php
declare(strict_types=1);

namespace Services;

final class CsvExportService
{
    /** @param list<array<string, mixed>> $applications */
    public function build(array $applications): string
    {
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            throw new \RuntimeException("Impossible de préparer l'export.");
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, [
            'Entreprise', 'Poste', 'Ville ou adresse', 'Type', 'Date de candidature',
            'Statut', 'Site utilisé', "URL de l'annonce", "Description de l'annonce", 'Nombre de relances', 'Notes',
        ], ';');

        foreach ($applications as $application) {
            fputcsv($stream, array_map([$this, 'safeCell'], [
                $application['company'],
                $application['position'],
                $application['location'],
                kind_label((string) $application['kind']),
                $application['applied_at'],
                status_label((string) $application['status']),
                $application['source_site'],
                $application['listing_url'],
                $application['job_description'],
                $application['follow_up_count'],
                $application['notes'],
            ]), ';');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        return $csv === false ? '' : $csv;
    }

    private function safeCell(mixed $value): string
    {
        $cell = (string) $value;
        if (preg_match('/^[=+\-@]/u', ltrim($cell)) === 1) {
            return "'" . $cell;
        }
        return $cell;
    }
}
