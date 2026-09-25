<?php
/**
 * Variables inherited from applications/show/index.php when this partial is included.
 *
 * @var array<string, mixed> $activity Current activity from the parent loop.
 * @var int $index Current activity index from the parent loop.
 * @var list<array<string, mixed>> $activities Complete activity list from the parent view.
 * @var int $id Current application identifier prepared by the parent view.
 * @var array<string, string> $activityTypes Available activity types from the controller.
 */
$activityId = (int) $activity['id'];
$isAutomatic = ($activity['origin'] ?? 'system') !== 'user' || $activity['type'] === 'application';
$occurredAtValue = (new DateTimeImmutable((string) $activity['occurred_at']))->format('Y-m-d\TH:i');
$dueAtValue = $activity['due_at'] !== null ? substr((string) $activity['due_at'], 0, 10) : '';
?>
<article id="activity-<?= $activityId ?>" class="relative grid grid-cols-[1rem_1fr] gap-4 <?= $index !== array_key_last($activities) ? 'pb-6' : '' ?>">
    <div class="relative">
        <span class="absolute left-1/2 top-2 h-3 w-3 -translate-x-1/2 rounded-full bg-brand"></span>
        <?php if ($index !== array_key_last($activities)): ?><span class="absolute left-1/2 top-5 h-full w-px -translate-x-1/2 bg-slate/20"></span><?php endif; ?>
    </div>
    <div class="min-w-0 pb-1">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="flex flex-wrap items-center gap-2">
                <strong><?= e(activity_label((string) $activity['type'])) ?></strong>
                <?php if ($isAutomatic): ?><span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-medium text-slate">Automatique</span><?php endif; ?>
            </span>
            <time class="text-xs text-slate"><?= e(format_date((string) $activity['occurred_at'], true)) ?></time>
        </div>
        <?php if ($activity['note'] !== ''): ?><p class="mt-2 whitespace-pre-wrap text-sm text-slate"><?= e($activity['note']) ?></p><?php endif; ?>
        <?php if ($activity['due_at'] !== null): ?><p class="mt-2 text-xs font-semibold text-brand-dark">Rappel ou événement prévu le <?= e(format_date((string) $activity['due_at'])) ?></p><?php endif; ?>
        <?php if ($activity['completed_at'] !== null): ?>
            <p class="mt-3 inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800">Effectuée le <?= e(format_date((string) $activity['completed_at'], true)) ?></p>
        <?php elseif ($activity['due_at'] !== null): ?>
            <form action="/applications/<?= $id ?>/activities/<?= $activityId ?>/complete" method="post" class="mt-3">
                <?= \CorianderCore\Core\Security\Csrf::input() ?>
                <button class="app-button app-button-ghost min-h-9 px-3 py-1.5 text-xs" type="submit">Marquer comme effectuée</button>
            </form>
        <?php endif; ?>

        <?php if (!$isAutomatic): ?>
            <div class="mt-3 flex flex-wrap gap-2">
                <button class="app-button app-button-ghost min-h-9 px-3 py-1.5 text-xs" type="button" aria-controls="activity-editor-<?= $activityId ?>" aria-expanded="false" data-activity-editor-toggle>Modifier</button>
                <form action="/applications/<?= $id ?>/activities/<?= $activityId ?>/delete" method="post" data-confirm="Supprimer définitivement cet événement ?">
                    <?= \CorianderCore\Core\Security\Csrf::input() ?>
                    <button class="app-button app-button-danger-ghost min-h-9 px-3 py-1.5 text-xs" type="submit">Supprimer</button>
                </form>
            </div>
            <div id="activity-editor-<?= $activityId ?>" class="mt-3 rounded-xl border border-slate/15 bg-slate-50 p-4" hidden data-activity-editor>
                <form action="/applications/<?= $id ?>/activities/<?= $activityId ?>/update" method="post" class="grid gap-4 sm:grid-cols-2">
                    <?= \CorianderCore\Core\Security\Csrf::input() ?>
                    <div>
                        <label class="app-label" for="activity_type_<?= $activityId ?>">Type</label>
                        <select class="app-input" id="activity_type_<?= $activityId ?>" name="type" required>
                            <?php foreach ($activityTypes as $value => $label): if ($value === 'application') continue; ?>
                                <option value="<?= e($value) ?>" <?= $activity['type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="app-label" for="activity_date_<?= $activityId ?>">Date et heure</label>
                        <input class="app-input" type="datetime-local" id="activity_date_<?= $activityId ?>" name="occurred_at" value="<?= e($occurredAtValue) ?>" required>
                    </div>
                    <div>
                        <label class="app-label" for="activity_due_<?= $activityId ?>">Date du prochain rappel ou événement</label>
                        <input class="app-input" type="date" id="activity_due_<?= $activityId ?>" name="due_at" value="<?= e($dueAtValue) ?>">
                    </div>
                    <div>
                        <label class="app-label" for="activity_note_<?= $activityId ?>">Note</label>
                        <textarea class="app-input min-h-24" id="activity_note_<?= $activityId ?>" name="note" rows="3"><?= e((string) $activity['note']) ?></textarea>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:col-span-2">
                        <button class="app-button min-h-9 px-3 py-1.5 text-xs" type="submit">Enregistrer</button>
                        <button class="app-button app-button-ghost min-h-9 px-3 py-1.5 text-xs" type="button" data-activity-editor-close="activity-editor-<?= $activityId ?>">Annuler</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</article>
