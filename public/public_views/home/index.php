<?php
/**
 * Data injected by Support\ViewResponse::render() from DashboardController::index().
 *
 * @var array{success: string|null, error: string|null} $flash Flash messages created by ViewResponse.
 * @var list<array{label: string, count: int, url: string}> $quickStats Dashboard totals prepared by the controller.
 * @var list<array<string, mixed>> $reviewApplications Applications requiring attention.
 * @var list<array<string, mixed>> $recentActivities Recently recorded activities.
 * @var list<array<string, mixed>> $upcomingActivities Upcoming dated activities.
 */
use CorianderCore\Core\Security\Csrf;
?>
<section class="flex flex-col gap-8">
    <!-- Flash messages -->
    <?php if ($flash['success']): ?><div class="app-alert border border-emerald-200 bg-emerald-50 text-emerald-800" role="status" data-flash><?= e($flash['success']) ?></div><?php endif; ?>
    <?php if ($flash['error']): ?><div class="app-alert border border-red-200 bg-red-50 text-red-800"><?= e($flash['error']) ?></div><?php endif; ?>

    <!-- Dashboard header -->
    <header class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div>
            <p class="mb-2 text-sm font-semibold uppercase tracking-2 text-brand">Vue d'ensemble</p>
            <h1 class="font-concert-one text-4xl text-black sm:text-5xl">Mes candidatures</h1>
            <p class="mt-3 max-w-2xl text-slate">Retrouvez rapidement une démarche et reprenez votre suivi là où vous l'avez laissé.</p>
        </div>
        <a href="/applications/create" class="app-button">Ajouter une candidature</a>
    </header>

    <!-- Quick application search -->
    <form action="/applications" method="get" class="app-card flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label class="app-label" for="dashboard-search">Rechercher une candidature</label>
            <input class="app-input" id="dashboard-search" name="q" placeholder="Entreprise, poste, ville ou contenu de l'annonce">
        </div>
        <button class="app-button" type="submit">Rechercher</button>
        <a href="/applications" class="app-button app-button-ghost">Filtres avancés</a>
    </form>

    <!-- Status summary -->
    <section aria-labelledby="status-summary-title">
        <div class="mb-3 flex items-center justify-between gap-4">
            <h2 id="status-summary-title" class="font-concert-one text-2xl">Vue rapide</h2>
            <a href="/applications" class="text-sm font-semibold text-brand hover:text-brand-dark">Toutes les candidatures</a>
        </div>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <?php foreach ($quickStats as $summary): ?>
                <a href="<?= e($summary['url']) ?>" class="app-card group flex min-h-24 items-center justify-between gap-3 p-4 transition hover:border-brand/40">
                    <span class="text-sm font-semibold text-slate group-hover:text-brand-dark"><?= e($summary['label']) ?></span>
                    <strong class="font-concert-one text-3xl text-brand-dark"><?= (int) $summary['count'] ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="<?= $upcomingActivities !== [] ? 'grid items-start gap-6 lg:grid-cols-[1.35fr_0.85fr]' : '' ?>">
        <div class="min-w-0 space-y-6">
            <?php if ($reviewApplications !== []): ?>
                <!-- Applications requiring review -->
                <section class="app-card p-6">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                        <div>
                            <h2 class="font-concert-one text-2xl">À examiner</h2>
                            <p class="mt-1 text-sm text-slate">Les trois candidatures en attente depuis le plus longtemps.</p>
                        </div>
                        <a href="/applications?status=waiting&amp;sort=oldest" class="shrink-0 text-sm font-semibold text-brand hover:text-brand-dark">Voir toutes</a>
                    </div>
                    <div class="mt-5 divide-y divide-slate/15">
                        <?php foreach ($reviewApplications as $application): ?>
                            <article class="flex flex-col justify-between gap-4 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center">
                                <div class="min-w-0">
                                    <a href="/applications/<?= (int) $application['id'] ?>" class="font-semibold hover:text-brand-dark"><?= e($application['company']) ?></a>
                                    <p class="mt-1 truncate text-sm text-slate"><?= e($application['position']) ?> · <?= e(application_age_label((string) $application['applied_at'])) ?></p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a href="/applications/<?= (int) $application['id'] ?>" class="app-button app-button-ghost min-h-9 px-3 py-1.5 text-xs">Consulter</a>
                                    <form action="/applications/<?= (int) $application['id'] ?>/status" method="post">
                                        <?= Csrf::input() ?>
                                        <input type="hidden" name="status" value="no_response">
                                        <button class="app-button app-button-secondary min-h-9 px-3 py-1.5 text-xs" type="submit">Sans réponse</button>
                                    </form>
                                    <form action="/applications/<?= (int) $application['id'] ?>/archive" method="post">
                                        <?= Csrf::input() ?>
                                        <button class="app-button app-button-ghost min-h-9 px-3 py-1.5 text-xs" type="submit">Archiver</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Recent activity -->
            <section class="app-card p-6">
                <div class="flex items-center justify-between gap-4">
                    <div><h2 class="font-concert-one text-2xl">Derniers changements</h2><p class="mt-1 text-sm text-slate">Les ajouts, réponses et événements les plus récents.</p></div>
                    <a href="/calendar" class="text-sm font-semibold text-brand hover:text-brand-dark">Calendrier</a>
                </div>
                <?php if ($recentActivities === []): ?>
                    <p class="mt-6 rounded-xl bg-brand-soft p-5 text-sm text-brand-dark">Aucun changement enregistré pour le moment.</p>
                <?php else: ?>
                    <div class="mt-5 divide-y divide-slate/15">
                        <?php foreach ($recentActivities as $activity): ?>
                            <a href="/applications/<?= (int) $activity['application_id'] ?>" class="flex items-start justify-between gap-4 py-4 first:pt-0 last:pb-0 hover:text-brand-dark">
                                <span class="min-w-0">
                                    <strong class="block truncate"><?= e($activity['company']) ?></strong>
                                    <span class="mt-1 block text-sm text-slate"><?= e(activity_label((string) $activity['type'])) ?><?= $activity['note'] !== '' ? ' · ' . e($activity['note']) : '' ?></span>
                                </span>
                                <time class="shrink-0 text-xs text-slate"><?= e(format_date((string) $activity['created_at'], true)) ?></time>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <?php if ($upcomingActivities !== []): ?>
            <!-- Upcoming events -->
            <aside class="min-w-0 space-y-6">
                <section class="app-card p-6">
                    <h2 class="font-concert-one text-2xl">À venir</h2>
                    <p class="mt-1 text-sm text-slate">Cette section apparaît uniquement lorsqu'un événement est planifié.</p>
                    <div class="mt-5 space-y-3">
                        <?php foreach ($upcomingActivities as $activity): ?>
                            <article class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                <span class="text-xs font-semibold uppercase tracking-1 text-amber-800"><?= e(format_date((string) $activity['due_at'])) ?></span>
                                <a href="/applications/<?= (int) $activity['application_id'] ?>" class="mt-1 block font-semibold hover:text-brand-dark"><?= e($activity['company']) ?></a>
                                <span class="block text-sm text-slate"><?= e(activity_label((string) $activity['type'])) ?></span>
                                <form action="/applications/<?= (int) $activity['application_id'] ?>/activities/<?= (int) $activity['id'] ?>/complete" method="post" class="mt-3">
                                    <?= Csrf::input() ?>
                                    <input type="hidden" name="return_to" value="home">
                                    <button class="app-button app-button-ghost min-h-9 px-3 py-1.5 text-xs" type="submit">Marquer comme effectuée</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </aside>
        <?php endif; ?>
    </div>
</section>
