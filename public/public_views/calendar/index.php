<?php
/**
 * Data injected by Support\ViewResponse::render() from CalendarController::index().
 *
 * @var DateTimeImmutable $firstDay First day of the selected month.
 * @var DateTimeImmutable $calendarStart First day displayed in the calendar grid.
 * @var DateTimeImmutable $calendarEnd Last day displayed in the calendar grid.
 * @var string|null $focusDate Date to reveal after following a dashboard task link.
 * @var array<string, list<array<string, mixed>>> $events Activities grouped by date.
 */
$months = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$previous = $firstDay->modify('-1 month')->format('Y-m');
$next = $firstDay->modify('+1 month')->format('Y-m');
$cursor = $calendarStart;
$weekdays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
?>
<section class="flex flex-col gap-7">
    <!-- Calendar page header -->
    <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div>
            <p class="mb-2 text-sm font-semibold uppercase tracking-2 text-brand">Suivi</p>
            <h1 class="font-concert-one text-4xl sm:text-5xl">Calendrier</h1>
            <p class="mt-3 text-slate">Historique et événements à venir au même endroit.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex flex-wrap items-center gap-3 text-xs font-semibold text-slate" aria-label="Légende du calendrier">
                <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full border border-amber-300 bg-amber-100" aria-hidden="true"></span>À venir</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full border border-blue-200 bg-brand-soft" aria-hidden="true"></span>Historique</span>
            </div>
            <a href="/applications" class="app-button app-button-ghost">Voir les candidatures</a>
        </div>
    </div>
    <section class="app-card overflow-hidden">
        <!-- Month navigation -->
        <header class="flex items-center justify-between gap-4 border-b border-slate/15 p-5">
            <a href="/calendar?month=<?= e($previous) ?>" class="app-button app-button-ghost" aria-label="Mois précédent">←</a>
            <h2 class="font-concert-one text-2xl capitalize"><?= e($months[(int) $firstDay->format('n')] . ' ' . $firstDay->format('Y')) ?></h2>
            <a href="/calendar?month=<?= e($next) ?>" class="app-button app-button-ghost" aria-label="Mois suivant">→</a>
        </header>
        <!-- Mobile event list -->
        <div class="divide-y divide-slate/15 md:hidden">
            <?php
            $mobileCursor = $firstDay;
            $hasMonthEvents = false;
            while ($mobileCursor <= $firstDay->modify('last day of this month')):
                $dateKey = $mobileCursor->format('Y-m-d');
                $dayEvents = $events[$dateKey] ?? [];
                if ($dayEvents === []) {
                    $mobileCursor = $mobileCursor->modify('+1 day');
                    continue;
                }
                $hasMonthEvents = true;
            ?>
                <section class="grid scroll-mt-24 grid-cols-[4.5rem_1fr] gap-4 p-4 <?= $dateKey === $focusDate ? 'ring-2 ring-inset ring-amber-300' : '' ?>" data-calendar-date="<?= e($dateKey) ?>" <?= $dateKey === $focusDate ? 'data-calendar-focus="true"' : '' ?>>
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-1 text-slate"><?= e($weekdays[(int) $mobileCursor->format('N') - 1]) ?></span>
                        <strong class="mt-1 block font-concert-one text-2xl"><?= e($mobileCursor->format('j')) ?></strong>
                    </div>
                    <div class="space-y-2">
                        <?php foreach ($dayEvents as $event): ?>
                            <?php require PROJECT_ROOT . '/public/public_views/calendar/event.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php $mobileCursor = $mobileCursor->modify('+1 day'); endwhile; ?>
            <?php if (!$hasMonthEvents): ?>
                <p class="p-8 text-center text-sm text-slate">Aucune activité enregistrée pour ce mois.</p>
            <?php endif; ?>
        </div>
        <!-- Desktop calendar grid -->
        <div class="hidden grid-cols-7 border-b border-slate/15 text-center text-xs font-semibold uppercase tracking-1 text-slate md:grid">
            <?php foreach ($weekdays as $day): ?><div class="p-3"><?= $day ?></div><?php endforeach; ?>
        </div>
        <div class="hidden md:grid md:grid-cols-7">
            <?php while ($cursor <= $calendarEnd):
                $dateKey = $cursor->format('Y-m-d');
                $dayEvents = $events[$dateKey] ?? [];
                $visibleEvents = array_slice($dayEvents, 0, 2);
                $additionalEvents = array_slice($dayEvents, 2);
            ?>
                <article class="min-h-32 scroll-mt-24 border-b border-r border-slate/15 p-3 <?= $cursor->format('m') !== $firstDay->format('m') ? 'bg-slate-50 text-slate' : 'bg-true-white' ?> <?= $dateKey === $focusDate ? 'ring-2 ring-inset ring-amber-300' : '' ?>" data-calendar-date="<?= e($dateKey) ?>" <?= $dateKey === $focusDate ? 'data-calendar-focus="true"' : '' ?>>
                    <div class="flex items-center justify-end">
                        <time class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold <?= $dateKey === date('Y-m-d') ? 'bg-brand text-white' : '' ?>"><?= e($cursor->format('j')) ?></time>
                    </div>
                    <div class="mt-2 space-y-2">
                        <?php foreach ($visibleEvents as $event): ?>
                            <?php require PROJECT_ROOT . '/public/public_views/calendar/event.php'; ?>
                        <?php endforeach; ?>
                        <?php if ($additionalEvents !== []): ?>
                            <details>
                                <summary class="cursor-pointer rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate hover:text-brand-dark">+ <?= count($additionalEvents) ?> autre<?= count($additionalEvents) > 1 ? 's' : '' ?></summary>
                                <div class="mt-2 space-y-2">
                                    <?php foreach ($additionalEvents as $event): ?>
                                        <?php require PROJECT_ROOT . '/public/public_views/calendar/event.php'; ?>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        <?php endif; ?>
                    </div>
                </article>
            <?php $cursor = $cursor->modify('+1 day'); endwhile; ?>
        </div>
    </section>
</section>
