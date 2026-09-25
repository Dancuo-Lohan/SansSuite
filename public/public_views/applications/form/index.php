<?php
/**
 * Data injected by Support\ViewResponse::render() from ApplicationController::formResponse().
 *
 * @var array<string, mixed> $values Form values prepared by the controller.
 * @var array<string, string> $errors Validation errors returned by the application service.
 * @var bool $editing Whether the form edits an existing application.
 * @var array<string, string> $kinds Available application kinds.
 * @var array<string, string> $statuses Available application statuses.
 */
use CorianderCore\Core\Security\Csrf;

$action = $editing ? '/applications/' . (int) $values['id'] . '/update' : '/applications';
$hasTextContent = $values['job_description'] !== '' || $values['cover_letter'] !== '' || $values['notes'] !== '';
?>
<section class="mx-auto max-w-4xl">
    <a href="<?= $editing ? '/applications/' . (int) $values['id'] : '/applications' ?>" class="text-sm font-semibold text-brand hover:text-brand-dark">← Retour</a>
    <div class="mt-5">
        <p class="mb-2 text-sm font-semibold uppercase tracking-2 text-brand"><?= $editing ? 'Modification' : 'Nouvelle démarche' ?></p>
        <h1 class="font-concert-one text-4xl sm:text-5xl"><?= $editing ? 'Modifier la candidature' : 'Ajouter une candidature' ?></h1>
        <p class="mt-3 text-slate">Les informations complémentaires restent facultatives.</p>
    </div>

    <?php if ($errors !== []): ?>
        <div class="app-alert mt-6 border border-red-200 bg-red-50 text-red-800">Certains champs sont à corriger.</div>
    <?php endif; ?>

    <form action="<?= e($action) ?>" method="post" enctype="multipart/form-data" class="mt-7 space-y-6">
        <?= Csrf::input() ?>
        <!-- Required application information -->
        <section class="app-card grid gap-5 p-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="app-label" for="kind">Type de candidature</label>
                <select class="app-input" id="kind" name="kind" required>
                    <?php foreach ($kinds as $value => $label): ?><option value="<?= e($value) ?>" <?= $values['kind'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                </select>
                <?php if (isset($errors['kind'])): ?><p class="app-error"><?= e($errors['kind']) ?></p><?php endif; ?>
            </div>

            <div>
                <label class="app-label" for="company">Entreprise *</label>
                <input class="app-input" id="company" name="company" value="<?= e($values['company']) ?>" required maxlength="160" autocomplete="organization">
                <?php if (isset($errors['company'])): ?><p class="app-error"><?= e($errors['company']) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="app-label" for="location">Adresse ou ville *</label>
                <input class="app-input" id="location" name="location" value="<?= e($values['location']) ?>" required autocomplete="street-address">
                <?php if (isset($errors['location'])): ?><p class="app-error"><?= e($errors['location']) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="app-label" for="position">Poste *</label>
                <input class="app-input" id="position" name="position" value="<?= e($values['position']) ?>" required>
                <?php if (isset($errors['position'])): ?><p class="app-error"><?= e($errors['position']) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="app-label" for="applied_at">Date de candidature *</label>
                <input class="app-input" type="date" id="applied_at" name="applied_at" value="<?= e($values['applied_at']) ?>" required>
                <?php if (isset($errors['applied_at'])): ?><p class="app-error"><?= e($errors['applied_at']) ?></p><?php endif; ?>
            </div>
        </section>

        <!-- Optional listing source and links -->
        <details class="app-card group p-6" <?= $values['source_site'] !== '' || $values['listing_url'] !== '' ? 'open' : '' ?>>
            <summary class="cursor-pointer list-none font-concert-one text-2xl">Annonce et liens <span class="ml-2 text-sm font-poppins font-normal text-slate">facultatif</span></summary>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="app-label" for="source_site">Site utilisé</label>
                    <input class="app-input" id="source_site" name="source_site" value="<?= e($values['source_site']) ?>" placeholder="LinkedIn, Indeed, France Travail…">
                </div>
                <div>
                    <label class="app-label" for="listing_url">URL de l'annonce</label>
                    <input class="app-input" type="url" id="listing_url" name="listing_url" value="<?= e($values['listing_url']) ?>" placeholder="https://…">
                    <?php if (isset($errors['listing_url'])): ?><p class="app-error"><?= e($errors['listing_url']) ?></p><?php endif; ?>
                </div>
            </div>
        </details>

        <!-- Optional listing content and personal notes -->
        <details class="app-card p-6" <?= !$editing && $hasTextContent ? 'open' : '' ?>>
            <summary class="cursor-pointer list-none font-concert-one text-2xl">Texte et notes <span class="ml-2 text-sm font-poppins font-normal text-slate"><?= $editing && $hasTextContent ? 'renseigné' : 'facultatif' ?></span></summary>
            <div class="mt-5 grid gap-5">
                <div><label class="app-label" for="job_description">Description de l'annonce</label><textarea class="app-input" id="job_description" name="job_description" rows="12" placeholder="Copiez-collez ici la description de l'annonce…"><?= e($values['job_description']) ?></textarea></div>
                <div><label class="app-label" for="cover_letter">Lettre de motivation</label><textarea class="app-input" id="cover_letter" name="cover_letter" rows="10"><?= e($values['cover_letter']) ?></textarea></div>
                <div><label class="app-label" for="notes">Notes personnelles</label><textarea class="app-input" id="notes" name="notes" rows="5"><?= e($values['notes']) ?></textarea></div>
            </div>
        </details>

        <?php if ($editing): ?>
            <!-- Optional interview preparation for progressed applications -->
            <details class="app-card p-6" <?= \Domain\ApplicationStatus::hasProgressed((string) $values['status']) ? '' : 'hidden' ?>>
                <summary class="cursor-pointer list-none font-concert-one text-2xl">Préparation d'entretien <span class="ml-2 text-sm font-poppins font-normal text-slate">facultatif</span></summary>
                <div class="mt-5 grid gap-5">
                    <div><label class="app-label" for="interview_preparation">Notes de préparation</label><textarea class="app-input" id="interview_preparation" name="interview_preparation"><?= e($values['interview_preparation']) ?></textarea></div>
                    <div><label class="app-label" for="interview_questions">Questions à poser</label><textarea class="app-input" id="interview_questions" name="interview_questions"><?= e($values['interview_questions']) ?></textarea></div>
                    <div><label class="app-label" for="interview_debrief">Ressenti après l'entretien</label><textarea class="app-input" id="interview_debrief" name="interview_debrief"><?= e($values['interview_debrief']) ?></textarea></div>
                </div>
            </details>
            <input type="hidden" name="status" value="<?= e($values['status']) ?>">
        <?php else: ?>
            <!-- Initial attachments for a new application -->
            <section class="app-card p-6">
                <label class="app-label" for="attachments">Pièces jointes</label>
                <input class="app-input" type="file" id="attachments" name="attachments[]" multiple accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg,.webp">
                <p class="app-help">PDF, Word, texte ou image — 5 Mo maximum par fichier.</p>
            </section>
        <?php endif; ?>

        <!-- Form actions -->
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a class="app-button app-button-ghost" href="<?= $editing ? '/applications/' . (int) $values['id'] : '/applications' ?>">Annuler</a>
            <button class="app-button" type="submit"><?= $editing ? 'Enregistrer les modifications' : 'Ajouter la candidature' ?></button>
        </div>
    </form>
</section>
