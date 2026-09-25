<?php
/**
 * Data injected by Support\ViewResponse::render() from ApplicationController::show().
 *
 * @var array{success: string|null, error: string|null} $flash Flash messages created by ViewResponse.
 * @var array<string, mixed> $application Application details returned by the repository.
 * @var list<array<string, mixed>> $activities Application activity history.
 * @var array<string, mixed>|null $rejectionActivity Latest rejection activity, when available.
 * @var list<array<string, mixed>> $contacts Contacts linked to the application.
 * @var list<array<string, mixed>> $attachments Attachments linked to the application.
 * @var array<string, string> $statuses Available application statuses.
 * @var array<string, string> $activityTypes Available activity types.
 */
use CorianderCore\Core\Security\Csrf;
use Domain\ApplicationStatus;

$id = (int) $application['id'];
$googleQuery = rawurlencode(trim((string) $application['company'] . ' ' . (string) $application['location']));
$progressed = ApplicationStatus::hasProgressed((string) $application['status']);
$activityActionLabel = match ((string) $application['status']) {
    'waiting' => 'Ajouter un retour',
    'contact', 'interview', 'offer' => 'Ajouter un échange',
    default => 'Ajouter une note',
};
$rejectionDate = $rejectionActivity !== null ? substr((string) $rejectionActivity['occurred_at'], 0, 10) : date('Y-m-d');
$rejectionNote = $rejectionActivity !== null ? (string) $rejectionActivity['note'] : '';
if (str_starts_with($rejectionNote, 'Statut :')) {
    $rejectionNote = '';
}
?>
<section class="flex flex-col gap-7">
    <!-- Flash messages -->
    <?php if ($flash['success']): ?>
        <div class="app-alert border border-emerald-200 bg-emerald-50 text-emerald-800" role="status" data-flash>
            <?= e($flash['success']) ?>
        </div>
    <?php endif; ?>
    <?php if ($flash['error']): ?>
        <div class="app-alert border border-red-200 bg-red-50 text-red-800"><?= e($flash['error']) ?></div>
    <?php endif; ?>

    <!-- Application identity and primary actions -->
    <div><a href="/applications" class="text-sm font-semibold text-brand hover:text-brand-dark">← Toutes les candidatures</a></div>
    <header class="flex flex-col justify-between gap-6 sm:flex-row sm:items-start">
        <div>
            <div class="flex flex-wrap items-start gap-3">
                <span class="flex flex-col items-start gap-1.5">
                    <span class="rounded-full border px-2.5 py-1 text-xs font-semibold <?= status_classes((string) $application['status']) ?>"><?= e(status_label((string) $application['status'])) ?></span>
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate"><?= e(application_age_label((string) $application['applied_at'])) ?></span>
                </span>
                <?php if ($application['archived_at'] !== null): ?>
                    <span class="rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">Archivée</span>
                <?php endif; ?>
            </div>
            <h1 class="mt-4 font-concert-one text-4xl sm:text-5xl"><?= e($application['company']) ?></h1>
            <p class="mt-2 text-xl font-semibold"><?= e($application['position']) ?></p>
            <p class="mt-2 text-slate">
                <?= e(kind_label((string) $application['kind'])) ?>
                · <?= e(format_date((string) $application['applied_at'])) ?>
                <?= $application['location'] !== '' ? ' · ' . e($application['location']) : '' ?>
            </p>
            <div class="mt-5 grid grid-cols-2 gap-2 sm:hidden">
                <button type="button" class="app-button app-button-secondary px-3" data-open-details="add-activity"><?= e($activityActionLabel) ?></button>
                <a href="/applications/<?= $id ?>/edit" class="app-button px-3">Modifier</a>
                <a href="https://www.google.com/search?q=<?= $googleQuery ?>" target="_blank" rel="noopener noreferrer" class="col-span-2 py-1 text-center text-sm font-semibold text-brand hover:text-brand-dark">Rechercher l'entreprise ↗</a>
            </div>
        </div>
        <div class="hidden flex-wrap gap-3 sm:flex">
            <a href="https://www.google.com/search?q=<?= $googleQuery ?>" target="_blank" rel="noopener noreferrer" class="app-button app-button-ghost">Rechercher l'entreprise</a>
            <button type="button" class="app-button app-button-secondary" data-open-details="add-activity"><?= e($activityActionLabel) ?></button>
            <a href="/applications/<?= $id ?>/edit" class="app-button">Modifier</a>
        </div>
    </header>

    <div class="grid gap-6 lg:grid-cols-[1.45fr_0.8fr]">
        <div class="min-w-0 space-y-6">
            <!-- Activity timeline and event creation -->
            <section id="suivi" class="app-card p-6">
                <div>
                    <h2 class="font-concert-one text-2xl">Suivi</h2>
                    <p class="mt-1 text-sm text-slate">Ajoutez autant de relances et d'échanges que nécessaire.</p>
                </div>
                <details id="add-activity" class="mt-5">
                    <summary class="app-button app-button-secondary w-fit list-none">Ajouter un événement</summary>
                    <form action="/applications/<?= $id ?>/activities" method="post" class="mt-4 grid gap-4 rounded-xl border border-brand/20 bg-brand-soft p-4 sm:grid-cols-2">
                        <?= Csrf::input() ?>
                        <div>
                            <label class="app-label" for="type">Type</label>
                            <select class="app-input" id="type" name="type" required>
                                <?php foreach ($activityTypes as $value => $label): ?>
                                    <?php if ($value === 'application') continue; ?>
                                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="app-label" for="occurred_at">Date et heure</label>
                            <input class="app-input" type="datetime-local" id="occurred_at" name="occurred_at" value="<?= date('Y-m-d\\TH:i') ?>" required>
                        </div>
                        <div>
                            <label class="app-label" for="due_at">Date du prochain rappel ou événement</label>
                            <input class="app-input" type="date" id="due_at" name="due_at">
                            <p class="app-help">Facultatif. Indiquez une date pour planifier une relance, un entretien ou une autre action. Elle apparaîtra dans le calendrier.</p>
                        </div>
                        <div>
                            <label class="app-label" for="activity_note">Note</label>
                            <textarea class="app-input min-h-24" id="activity_note" name="note" rows="3"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <button class="app-button" type="submit">Ajouter au suivi</button>
                        </div>
                    </form>
                </details>

                <div class="mt-7">
                    <?php foreach ($activities as $index => $activity): ?>
                        <?php require PROJECT_ROOT . '/public/public_views/applications/show/activity.php'; ?>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($application['job_description'] !== '' || $application['cover_letter'] !== '' || $application['notes'] !== ''): ?>
                <!-- Saved listing content and personal notes -->
                <details class="app-card app-disclosure p-6">
                    <summary>
                        <span>
                            <strong class="font-concert-one text-2xl">Textes et notes</strong>
                            <small>Annonce, lettre et notes personnelles</small>
                        </span>
                    </summary>
                    <?php if ($application['job_description'] !== ''): ?>
                        <details class="mt-5 rounded-xl border border-slate/15 p-4">
                            <summary class="cursor-pointer font-semibold">Description de l'annonce</summary>
                            <p class="mt-4 whitespace-pre-wrap text-sm leading-7 text-slate"><?= e($application['job_description']) ?></p>
                        </details>
                    <?php endif; ?>
                    <?php if ($application['cover_letter'] !== ''): ?>
                        <details class="mt-5 rounded-xl border border-slate/15 p-4">
                            <summary class="cursor-pointer font-semibold">Lettre de motivation</summary>
                            <p class="mt-4 whitespace-pre-wrap text-sm leading-7 text-slate"><?= e($application['cover_letter']) ?></p>
                        </details>
                    <?php endif; ?>
                    <?php if ($application['notes'] !== ''): ?>
                        <div class="mt-5">
                            <h3 class="font-semibold">Notes personnelles</h3>
                            <p class="mt-2 whitespace-pre-wrap text-sm leading-7 text-slate"><?= e($application['notes']) ?></p>
                        </div>
                    <?php endif; ?>
                </details>
            <?php endif; ?>

            <?php if ($progressed): ?>
                <!-- Contacts available after the application has progressed -->
                <details id="contacts" class="app-card app-disclosure p-6">
                    <summary>
                        <span>
                            <strong class="font-concert-one text-2xl">Contacts</strong>
                            <small><?= count($contacts) ?> enregistré<?= count($contacts) > 1 ? 's' : '' ?></small>
                        </span>
                    </summary>
                    <p class="mt-4 text-sm text-slate">Cette section apparaît après le premier retour de l'entreprise.</p>

                    <?php if ($contacts !== []): ?>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <?php foreach ($contacts as $contact): ?>
                                <article class="rounded-xl border border-slate/15 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <strong><?= e($contact['name']) ?></strong>
                                            <?php if ($contact['role'] !== ''): ?><p class="text-sm text-slate"><?= e($contact['role']) ?></p><?php endif; ?>
                                        </div>
                                        <form action="/applications/<?= $id ?>/contacts/<?= (int) $contact['id'] ?>/delete" method="post">
                                            <?= Csrf::input() ?>
                                            <button type="submit" class="text-xs font-semibold text-red-700">Supprimer</button>
                                        </form>
                                    </div>
                                    <div class="mt-3 space-y-1 text-sm">
                                        <?php if ($contact['email'] !== ''): ?><a class="block text-brand" href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a><?php endif; ?>
                                        <?php if ($contact['phone'] !== ''): ?><a class="block text-brand" href="tel:<?= e($contact['phone']) ?>"><?= e($contact['phone']) ?></a><?php endif; ?>
                                        <?php if ($contact['linkedin_url'] !== ''): ?><a class="block text-brand" href="<?= e($contact['linkedin_url']) ?>" target="_blank" rel="noopener noreferrer">Profil LinkedIn</a><?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <details class="mt-5 rounded-xl border border-slate/15 p-4">
                        <summary class="cursor-pointer font-semibold text-brand-dark">Ajouter un contact</summary>
                        <form action="/applications/<?= $id ?>/contacts" method="post" class="mt-5 grid gap-4 sm:grid-cols-2">
                            <?= Csrf::input() ?>
                            <div><label class="app-label" for="contact_name">Nom *</label><input class="app-input" id="contact_name" name="name" required></div>
                            <div><label class="app-label" for="role">Fonction</label><input class="app-input" id="role" name="role"></div>
                            <div><label class="app-label" for="email">E-mail</label><input class="app-input" type="email" id="email" name="email"></div>
                            <div><label class="app-label" for="phone">Téléphone</label><input class="app-input" id="phone" name="phone"></div>
                            <div class="sm:col-span-2"><label class="app-label" for="linkedin_url">Profil LinkedIn</label><input class="app-input" type="url" id="linkedin_url" name="linkedin_url" placeholder="https://…"></div>
                            <div class="sm:col-span-2"><button class="app-button" type="submit">Ajouter le contact</button></div>
                        </form>
                    </details>
                </details>

                <?php if ($application['interview_preparation'] !== '' || $application['interview_questions'] !== '' || $application['interview_debrief'] !== ''): ?>
                    <!-- Interview preparation and debrief -->
                    <details class="app-card app-disclosure p-6">
                        <summary><span><strong class="font-concert-one text-2xl">Préparation d'entretien</strong><small>Notes facultatives</small></span></summary>
                        <div class="mt-5 flex justify-end"><a href="/applications/<?= $id ?>/edit" class="text-sm font-semibold text-brand">Modifier</a></div>
                        <div class="mt-5 space-y-5">
                            <?php foreach (['interview_preparation' => 'Notes de préparation', 'interview_questions' => 'Questions à poser', 'interview_debrief' => "Ressenti après l'entretien"] as $field => $label): ?>
                                <?php if ($application[$field] === '') continue; ?>
                                <div>
                                    <h3 class="font-semibold"><?= e($label) ?></h3>
                                    <p class="mt-2 whitespace-pre-wrap text-sm leading-7 text-slate"><?= e($application[$field]) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <aside class="min-w-0 space-y-6">
            <!-- Application status management -->
            <section class="app-card p-5">
                <h2 class="font-concert-one text-xl">Statut</h2>
                <form
                    action="/applications/<?= $id ?>/status"
                    method="post"
                    class="mt-4 space-y-3"
                    data-status-form
                    data-current-status="<?= e((string) $application['status']) ?>"
                    data-initial-rejection-date="<?= e($rejectionDate) ?>"
                    data-initial-rejection-note="<?= e($rejectionNote) ?>"
                >
                    <?= Csrf::input() ?>
                    <label class="app-label" for="status">Étape actuelle</label>
                    <select class="app-input" id="status" name="status">
                        <?php foreach ($statuses as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $application['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="space-y-3 rounded-xl border border-red-200 bg-red-50 p-4 <?= $application['status'] === 'rejected' ? '' : 'hidden' ?>" data-rejection-fields>
                        <div>
                            <label class="app-label" for="rejection_date">Date du refus *</label>
                            <input class="app-input" type="date" id="rejection_date" name="rejection_date" value="<?= e($rejectionDate) ?>">
                        </div>
                        <div>
                            <label class="app-label" for="rejection_note">Commentaire sur le refus</label>
                            <textarea class="app-input min-h-24" id="rejection_note" name="rejection_note" rows="3" placeholder="Retour reçu, raison évoquée…"><?= e($rejectionNote) ?></textarea>
                        </div>
                    </div>
                    <button class="app-button w-full" type="submit" data-status-submit>Mettre à jour</button>
                </form>
            </section>

            <!-- Saved listing links -->
            <details class="app-card app-disclosure p-5">
                <summary><span><strong class="font-concert-one text-xl">Annonce</strong><small>Liens enregistrés</small></span></summary>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="font-semibold text-slate">Site utilisé</dt>
                        <dd class="mt-1">
                            <?php if ($application['source_site'] !== '' && filter_var($application['source_site'], FILTER_VALIDATE_URL)): ?>
                                <a href="<?= e($application['source_site']) ?>" target="_blank" rel="noopener noreferrer" class="break-all text-brand">Ouvrir le site</a>
                            <?php elseif ($application['source_site'] !== ''): ?>
                                <?= e($application['source_site']) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate">Annonce</dt>
                        <dd class="mt-1">
                            <?php if ($application['listing_url'] !== ''): ?>
                                <a href="<?= e($application['listing_url']) ?>" target="_blank" rel="noopener noreferrer" class="break-all text-brand">Ouvrir l'annonce</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </details>

            <!-- Attachment management -->
            <details id="documents" class="app-card app-disclosure p-5">
                <summary>
                    <span>
                        <strong class="font-concert-one text-xl">Pièces jointes</strong>
                        <small><?= count($attachments) ?> fichier<?= count($attachments) > 1 ? 's' : '' ?></small>
                    </span>
                </summary>
                <?php if ($attachments === []): ?>
                    <p class="mt-3 text-sm text-slate">Aucun fichier joint.</p>
                <?php else: ?>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($attachments as $attachment): ?>
                            <div class="rounded-xl border border-slate/15 p-3">
                                <a href="/attachments/<?= (int) $attachment['id'] ?>/download" class="block break-all text-sm font-semibold text-brand"><?= e($attachment['original_name']) ?></a>
                                <div class="mt-2 flex items-center justify-between text-xs text-slate">
                                    <span><?= e(number_format((int) $attachment['size'] / 1024, 0, ',', ' ')) ?> Ko</span>
                                    <form action="/applications/<?= $id ?>/attachments/<?= (int) $attachment['id'] ?>/delete" method="post">
                                        <?= Csrf::input() ?>
                                        <button type="submit" class="font-semibold text-red-700">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="/applications/<?= $id ?>/attachments" method="post" enctype="multipart/form-data" class="mt-5">
                    <?= Csrf::input() ?>
                    <label class="app-label" for="attachments">Ajouter des fichiers</label>
                    <input class="app-input" type="file" id="attachments" name="attachments[]" multiple accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg,.webp">
                    <button class="app-button app-button-secondary mt-3 w-full" type="submit">Ajouter</button>
                </form>
            </details>

            <!-- Archive and permanent deletion actions -->
            <section class="app-card p-5">
                <h2 class="font-concert-one text-xl">Gestion</h2>
                <?php if ($application['archived_at'] === null): ?>
                    <form action="/applications/<?= $id ?>/archive" method="post" class="mt-4">
                        <?= Csrf::input() ?>
                        <button class="app-button app-button-ghost w-full" type="submit">Archiver</button>
                    </form>
                <?php else: ?>
                    <form action="/applications/<?= $id ?>/restore" method="post" class="mt-4">
                        <?= Csrf::input() ?>
                        <button class="app-button app-button-secondary w-full" type="submit">Restaurer</button>
                    </form>
                <?php endif; ?>
                <details class="mt-5 border-t border-slate/15 pt-4">
                    <summary class="cursor-pointer text-sm font-semibold text-red-700">Supprimer définitivement</summary>
                    <p class="mt-3 text-xs text-slate">La candidature, son historique et ses fichiers seront supprimés.</p>
                    <form action="/applications/<?= $id ?>/delete" method="post" class="mt-3" data-confirm="Supprimer définitivement cette candidature ?">
                        <?= Csrf::input() ?>
                        <button class="app-button app-button-danger w-full" type="submit">Confirmer la suppression</button>
                    </form>
                </details>
            </section>
        </aside>
    </div>
</section>
