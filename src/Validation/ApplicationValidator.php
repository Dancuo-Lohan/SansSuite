<?php
declare(strict_types=1);

namespace Validation;

use Domain\ApplicationKind;
use Domain\ApplicationStatus;

final class ApplicationValidator
{
    /**
     * @param array<string, mixed> $input
     * @return array{data: array<string, string>, errors: array<string, string>}
     */
    public function validate(array $input, bool $editing = false): array
    {
        $fields = [
            'kind', 'company', 'location', 'position', 'applied_at', 'source_site',
            'listing_url', 'job_description', 'cover_letter', 'notes', 'status', 'interview_preparation',
            'interview_questions', 'interview_debrief',
        ];
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = trim((string) ($input[$field] ?? ''));
        }

        if (!$editing) {
            $data['status'] = 'waiting';
        }

        $errors = [];
        if (!ApplicationKind::isValid($data['kind'])) {
            $errors['kind'] = 'Choisissez un type de candidature.';
        }
        if ($data['company'] === '') {
            $errors['company'] = "Indiquez le nom de l'entreprise.";
        }
        if (mb_strlen($data['company']) > 160) {
            $errors['company'] = "Le nom de l'entreprise est trop long.";
        }
        if ($data['position'] === '') {
            $errors['position'] = 'Indiquez le poste recherché.';
        }
        if ($data['location'] === '') {
            $errors['location'] = "Indiquez l'adresse ou au minimum la ville de l'entreprise.";
        }
        if ($data['applied_at'] === '' || !$this->isDate($data['applied_at'])) {
            $errors['applied_at'] = 'Indiquez une date de candidature valide.';
        }
        if (!ApplicationStatus::isValid($data['status'])) {
            $errors['status'] = 'Choisissez un statut valide.';
        }
        foreach (['listing_url'] as $urlField) {
            if ($data[$urlField] !== '' && filter_var($data[$urlField], FILTER_VALIDATE_URL) === false) {
                $errors[$urlField] = 'Utilisez une adresse complète commençant par http:// ou https://.';
            }
        }

        return ['data' => $data, 'errors' => $errors];
    }

    private function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
