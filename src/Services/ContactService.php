<?php
declare(strict_types=1);

namespace Services;

use Repositories\ContactRepository;

final class ContactService
{
    public function __construct(private ContactRepository $contacts) {}

    /** @param array<string, mixed> $input @return array<string, string> */
    public function create(int $applicationId, array $input): array
    {
        $data = [
            'name' => trim((string) ($input['name'] ?? '')),
            'role' => trim((string) ($input['role'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? '')),
            'linkedin_url' => trim((string) ($input['linkedin_url'] ?? '')),
        ];
        $errors = [];
        if ($data['name'] === '') {
            $errors['name'] = 'Indiquez le nom du contact.';
        }
        if ($data['email'] !== '' && filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = "L'adresse e-mail n'est pas valide.";
        }
        if ($data['linkedin_url'] !== '' && filter_var($data['linkedin_url'], FILTER_VALIDATE_URL) === false) {
            $errors['linkedin_url'] = "L'adresse LinkedIn n'est pas valide.";
        }
        if ($errors === []) {
            $this->contacts->create($applicationId, $data);
        }
        return $errors;
    }
}
