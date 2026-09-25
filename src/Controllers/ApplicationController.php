<?php
declare(strict_types=1);

namespace Controllers;

use Domain\ActivityType;
use Domain\ApplicationKind;
use Domain\ApplicationStatus;
use Domain\StatusChangeResult;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Repositories\ActivityRepository;
use Repositories\ApplicationRepository;
use Repositories\AttachmentRepository;
use Repositories\ContactRepository;
use Services\ApplicationService;
use Services\AttachmentStorage;
use Services\ContactService;
use Support\Flash;
use Support\ViewResponse;

final class ApplicationController
{
    public function __construct(
        private ApplicationRepository $applications,
        private ActivityRepository $activities,
        private ContactRepository $contacts,
        private AttachmentRepository $attachments,
        private ApplicationService $service,
        private ContactService $contactService,
        private AttachmentStorage $storage,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $filters = array_map(static fn(mixed $value): string => trim((string) $value), $request->getQueryParams());
        $filters += ['q' => '', 'status' => '', 'kind' => '', 'from' => '', 'to' => '', 'follow_up' => '', 'archive' => 'active', 'sort' => 'recent'];

        return ViewResponse::render('applications/index', [
            'metadata' => '<title>Candidatures — Sans Suite</title><meta name="description" content="Recherchez et filtrez vos candidatures.">',
            'applications' => $this->applications->search($filters),
            'filters' => $filters,
            'statuses' => ApplicationStatus::labels(),
            'kinds' => ApplicationKind::labels(),
        ]);
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        unset($request);
        return $this->formResponse([
            'kind' => 'listing',
            'company' => '',
            'location' => '',
            'position' => '',
            'applied_at' => date('Y-m-d'),
            'source_site' => '',
            'listing_url' => '',
            'job_description' => '',
            'cover_letter' => '',
            'notes' => '',
            'status' => 'waiting',
            'interview_preparation' => '',
            'interview_questions' => '',
            'interview_debrief' => '',
        ], [], false);
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $input = $this->body($request);
        $result = $this->service->create($input);
        if ($result['id'] === null) {
            return $this->formResponse($result['data'], $result['errors'], false, 422);
        }

        $uploadErrors = $this->storage->storeMany($result['id'], $_FILES['attachments'] ?? []);
        if ($uploadErrors !== []) {
            Flash::error(implode(' ', $uploadErrors));
        } else {
            Flash::success('La candidature a été ajoutée.');
        }
        return ViewResponse::redirect('/applications/' . $result['id']);
    }

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }

        $id = (int) $application['id'];
        return ViewResponse::render('applications/show', [
            'metadata' => '<title>' . e($application['company']) . ' — Sans Suite</title><meta name="description" content="Suivi de candidature.">',
            'application' => $application,
            'activities' => $this->activities->forApplication($id),
            'rejectionActivity' => $this->activities->latestSystemOfType($id, 'rejection'),
            'contacts' => $this->contacts->forApplication($id),
            'attachments' => $this->attachments->forApplication($id),
            'statuses' => ApplicationStatus::labels(),
            'activityTypes' => ActivityType::labels(),
        ]);
    }

    public function edit(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        return $application === null ? $this->notFound() : $this->formResponse($application, [], true);
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        $result = $this->service->update((int) $application['id'], $this->body($request));
        if ($result['errors'] !== []) {
            return $this->formResponse($result['data'] + ['id' => (int) $application['id']], $result['errors'], true, 422);
        }
        Flash::success('Les informations ont été mises à jour.');
        return ViewResponse::redirect('/applications/' . $application['id']);
    }

    public function addActivity(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        $errors = $this->service->addActivity((int) $application['id'], $this->body($request));
        $errors === [] ? Flash::success("L'événement a été ajouté.") : Flash::error(implode(' ', $errors));
        return ViewResponse::redirect('/applications/' . $application['id'] . '#suivi');
    }

    public function updateActivity(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        $activityId = (int) $request->getAttribute('activityId');
        $errors = $this->service->updateActivity((int) $application['id'], $activityId, $this->body($request));
        $errors === []
            ? Flash::success("L'événement a été modifié.")
            : Flash::error(implode(' ', $errors));
        return ViewResponse::redirect('/applications/' . $application['id'] . '#activity-' . $activityId);
    }

    public function completeActivity(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        $activityId = (int) $request->getAttribute('activityId');
        $errors = $this->service->completeActivity((int) $application['id'], $activityId);
        $errors === []
            ? Flash::success('La tâche a été marquée comme effectuée.')
            : Flash::error(implode(' ', $errors));

        $returnTo = (string) ($this->body($request)['return_to'] ?? '');
        return ViewResponse::redirect(
            $returnTo === 'home'
                ? '/home'
                : '/applications/' . $application['id'] . '#activity-' . $activityId,
        );
    }

    public function deleteActivity(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        $errors = $this->service->deleteActivity(
            (int) $application['id'],
            (int) $request->getAttribute('activityId'),
        );
        $errors === []
            ? Flash::success("L'événement a été supprimé.")
            : Flash::error(implode(' ', $errors));
        return ViewResponse::redirect('/applications/' . $application['id'] . '#suivi');
    }

    public function changeStatus(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        $body = $this->body($request);
        $status = trim((string) ($body['status'] ?? ''));
        $result = $this->service->changeStatus(
            (int) $application['id'],
            $status,
            isset($body['rejection_date']) ? (string) $body['rejection_date'] : null,
            isset($body['rejection_note']) ? (string) $body['rejection_note'] : null,
        );
        if ($result->outcome === StatusChangeResult::CHANGED) {
            Flash::success('Le statut a été mis à jour.');
        } elseif ($result->outcome === StatusChangeResult::UNCHANGED) {
            Flash::success('Aucun changement à enregistrer.');
        } else {
            Flash::error(implode(' ', $result->errors));
        }
        return ViewResponse::redirect('/applications/' . $application['id']);
    }

    public function archive(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        $this->applications->archive((int) $application['id']);
        Flash::success('La candidature a été archivée.');
        return ViewResponse::redirect('/applications');
    }

    public function restore(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        $this->applications->restore((int) $application['id']);
        Flash::success('La candidature a été restaurée.');
        return ViewResponse::redirect('/applications/' . $application['id']);
    }

    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        $files = $this->attachments->forApplicationBeforeDelete((int) $application['id']);
        $this->applications->delete((int) $application['id']);
        foreach ($files as $file) {
            $this->storage->delete((string) $file['stored_name']);
        }
        Flash::success('La candidature a été supprimée définitivement.');
        return ViewResponse::redirect('/applications');
    }

    public function upload(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        $errors = $this->storage->storeMany((int) $application['id'], $_FILES['attachments'] ?? []);
        $errors === [] ? Flash::success('Les fichiers ont été ajoutés.') : Flash::error(implode(' ', $errors));
        return ViewResponse::redirect('/applications/' . $application['id'] . '#documents');
    }

    public function download(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getAttribute('attachmentId');
        $attachment = $this->attachments->find($id);
        if ($attachment === null) {
            return $this->notFound();
        }
        $path = $this->storage->path((string) $attachment['stored_name']);
        if ($path === null) {
            return $this->notFound();
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            return new Response(500, [], 'Fichier indisponible');
        }
        $name = rawurlencode((string) $attachment['original_name']);
        return new Response(200, [
            'Content-Type' => (string) $attachment['mime_type'],
            'Content-Disposition' => "attachment; filename*=UTF-8''{$name}",
            'Content-Length' => (string) strlen($contents),
            'X-Content-Type-Options' => 'nosniff',
        ], $contents);
    }

    public function deleteAttachment(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        $attachment = $this->attachments->find((int) $request->getAttribute('attachmentId'));
        if ($attachment !== null && (int) $attachment['application_id'] === (int) $application['id']) {
            $this->attachments->deleteForApplication((int) $attachment['id'], (int) $application['id']);
            $this->storage->delete((string) $attachment['stored_name']);
            Flash::success('Le fichier a été supprimé.');
        }
        return ViewResponse::redirect('/applications/' . $application['id'] . '#documents');
    }

    public function addContact(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        if (!ApplicationStatus::hasProgressed((string) $application['status'])) {
            Flash::error("Les contacts deviennent disponibles après le premier retour de l'entreprise.");
            return ViewResponse::redirect('/applications/' . $application['id']);
        }
        $errors = $this->contactService->create((int) $application['id'], $this->body($request));
        $errors === [] ? Flash::success('Le contact a été ajouté.') : Flash::error(implode(' ', $errors));
        return ViewResponse::redirect('/applications/' . $application['id'] . '#contacts');
    }

    public function deleteContact(ServerRequestInterface $request): ResponseInterface
    {
        $application = $this->findFromRequest($request);
        if ($application === null) {
            return $this->notFound();
        }
        $this->contacts->deleteForApplication((int) $request->getAttribute('contactId'), (int) $application['id']);
        Flash::success('Le contact a été supprimé.');
        return ViewResponse::redirect('/applications/' . $application['id'] . '#contacts');
    }

    /** @param array<string, mixed> $values @param array<string, string> $errors */
    private function formResponse(array $values, array $errors, bool $editing, int $status = 200): ResponseInterface
    {
        return ViewResponse::render('applications/form', [
            'metadata' => '<title>' . ($editing ? 'Modifier' : 'Ajouter') . ' une candidature — Sans Suite</title>',
            'values' => $values,
            'errors' => $errors,
            'editing' => $editing,
            'kinds' => ApplicationKind::labels(),
            'statuses' => ApplicationStatus::labels(),
        ], $status);
    }

    /** @return array<string, mixed> */
    private function body(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }

    /** @return array<string, mixed>|null */
    private function findFromRequest(ServerRequestInterface $request): ?array
    {
        return $this->applications->find((int) $request->getAttribute('id'));
    }

    private function notFound(): ResponseInterface
    {
        return ViewResponse::render('notfound', [
            'metadata' => '<title>Introuvable — Sans Suite</title>',
        ], 404);
    }
}
