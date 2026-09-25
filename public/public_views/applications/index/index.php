<?php
/**
 * Data injected by Support\ViewResponse::render() from ApplicationController::index().
 *
 * @var array{success: string|null, error: string|null} $flash Flash messages created by ViewResponse.
 * @var list<array<string, mixed>> $applications Applications returned by the repository search.
 * @var array<string, string> $filters Normalized query filters.
 * @var array<string, string> $statuses Available application statuses.
 * @var array<string, string> $kinds Available application kinds.
 */
$exportQuery = http_build_query(array_filter($filters, static fn(string $value): bool => $value !== ''));
$hasAdvancedFilters = $filters['kind'] !== ''
    || $filters['from'] !== ''
    || $filters['to'] !== ''
    || $filters['follow_up'] !== ''
    || $filters['archive'] !== 'active'
    || $filters['sort'] !== 'recent';
?>
<section class="flex flex-col gap-7">
    <!-- Flash messages -->
    <?php if ($flash['success']): ?>
        <div class="app-alert border border-emerald-200 bg-emerald-50 text-emerald-800" role="status" data-flash><?= e($flash['success']) ?></div>
    <?php endif; ?>
    <?php if ($flash['error']): ?>
        <div class="app-alert border border-red-200 bg-red-50 text-red-800"><?= e($flash['error']) ?></div>
    <?php endif; ?>

    <!-- Applications page header -->
    <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div>
            <p class="mb-2 text-sm font-semibold uppercase tracking-2 text-brand">Suivi</p>
            <h1 class="font-concert-one text-4xl sm:text-5xl">Candidatures</h1>
            <p class="mt-3 text-slate"><?= count($applications) ?> résultat<?= count($applications) > 1 ? 's' : '' ?></p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="/export/csv?<?= e($exportQuery) ?>" class="app-button app-button-ghost">Exporter en CSV</a>
            <a href="/applications/create" class="app-button">Ajouter</a>
        </div>
    </div>

    <!-- Search and combined filters -->
    <form method="get" action="/applications" class="app-card p-5">
        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_14rem_auto] md:items-end">
            <div>
                <label class="app-label" for="q">Rechercher</label>
                <input class="app-input" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Entreprise, poste, ville ou notes">
            </div>
            <div>
                <label class="app-label" for="status">Statut</label>
                <select class="app-input" id="status" name="status">
                    <option value="">Tous les statuts</option>
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="app-button" type="submit">Rechercher</button>
        </div>
        <details class="app-filter-disclosure mt-4 border-t border-slate/15 pt-4" <?= $hasAdvancedFilters ? 'open' : '' ?>>
            <summary class="cursor-pointer text-sm font-semibold text-brand-dark">Plus de filtres<?= $hasAdvancedFilters ? ' · actifs' : '' ?></summary>
            <div class="mt-4 grid gap-4 md:grid-cols-4">
                <div>
                    <label class="app-label" for="kind">Type</label>
                    <select class="app-input" id="kind" name="kind">
                        <option value="">Tous les types</option>
                        <?php foreach ($kinds as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $filters['kind'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="app-label" for="from">Depuis</label><input class="app-input" type="date" id="from" name="from" value="<?= e($filters['from']) ?>"></div>
                <div><label class="app-label" for="to">Jusqu'au</label><input class="app-input" type="date" id="to" name="to" value="<?= e($filters['to']) ?>"></div>
                <div>
                    <label class="app-label" for="follow_up">Relances</label>
                    <select class="app-input" id="follow_up" name="follow_up">
                        <option value="">Toutes</option>
                        <option value="yes" <?= $filters['follow_up'] === 'yes' ? 'selected' : '' ?>>Avec relance</option>
                        <option value="no" <?= $filters['follow_up'] === 'no' ? 'selected' : '' ?>>Sans relance</option>
                    </select>
                </div>
                <div>
                    <label class="app-label" for="archive">Affichage</label>
                    <select class="app-input" id="archive" name="archive">
                        <option value="active" <?= $filters['archive'] === 'active' ? 'selected' : '' ?>>Actives</option>
                        <option value="archived" <?= $filters['archive'] === 'archived' ? 'selected' : '' ?>>Archivées</option>
                        <option value="all" <?= $filters['archive'] === 'all' ? 'selected' : '' ?>>Toutes</option>
                    </select>
                </div>
                <div>
                    <label class="app-label" for="sort">Trier par</label>
                    <select class="app-input" id="sort" name="sort">
                        <option value="recent" <?= $filters['sort'] === 'recent' ? 'selected' : '' ?>>Plus récentes</option>
                        <option value="oldest" <?= $filters['sort'] === 'oldest' ? 'selected' : '' ?>>Plus anciennes</option>
                        <option value="company" <?= $filters['sort'] === 'company' ? 'selected' : '' ?>>Entreprise</option>
                        <option value="activity" <?= $filters['sort'] === 'activity' ? 'selected' : '' ?>>Dernière activité</option>
                    </select>
                </div>
                <div class="flex items-end gap-3 md:col-span-4">
                    <button class="app-button app-button-secondary" type="submit">Appliquer les filtres</button>
                    <a href="/applications" class="app-button app-button-ghost">Réinitialiser</a>
                </div>
            </div>
        </details>
    </form>

    <!-- Application search results -->
    <?php if ($applications === []): ?>
        <div class="app-card p-10 text-center">
            <h2 class="font-concert-one text-2xl">Aucune candidature trouvée</h2>
            <p class="mt-2 text-slate">Modifiez les filtres ou ajoutez une nouvelle démarche.</p>
        </div>
    <?php else: ?>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($applications as $application): ?>
                <a href="/applications/<?= (int) $application['id'] ?>" class="app-card group flex min-h-52 flex-col p-4 transition duration-150 hover:-translate-y-0.5 hover:border-brand/40 hover:shadow-lg <?= $application['archived_at'] !== null ? 'bg-slate-50' : '' ?>">
                    <h2 class="app-clamp-2 font-concert-one text-xl leading-tight transition-colors group-hover:text-brand-dark"><?= e($application['company']) ?></h2>
                    <p class="app-clamp-2 mt-2 text-sm font-semibold leading-6"><?= e($application['position']) ?></p>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold <?= status_classes((string) $application['status']) ?>"><?= e(status_label((string) $application['status'])) ?></span>
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate"><?= e(application_age_label((string) $application['applied_at'])) ?></span>
                        <?php if ($application['archived_at'] !== null): ?>
                            <span class="rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">Archivée</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-auto border-t border-slate/15 pt-4 text-xs text-slate">
                        <?php if ($application['location'] !== ''): ?>
                            <span class="block truncate"><?= e($application['location']) ?></span>
                        <?php endif; ?>
                        <span class="<?= $application['location'] !== '' ? 'mt-1 ' : '' ?>block">Candidature du <?= e(format_date((string) $application['applied_at'])) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
