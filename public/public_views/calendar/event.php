<?php
/**
 * Variable inherited from calendar/index.php when this partial is included.
 *
 * @var array<string, mixed> $event Current calendar event from the parent loop.
 */
$isUpcoming = ($event['calendar_is_upcoming'] ?? false) === true;
$isPlanned = ($event['calendar_kind'] ?? '') === 'planned';
$isCompleted = ($event['calendar_kind'] ?? '') === 'completed';
?>
<a href="/applications/<?= (int) $event['application_id'] ?>" class="block rounded-lg border p-2 text-xs <?= $isUpcoming ? 'border-amber-300 bg-amber-100 text-amber-950' : 'border-blue-200 bg-brand-soft text-brand-dark' ?>">
    <strong class="block truncate"><?= e($event['company']) ?></strong>
    <span class="block truncate"><?= $isUpcoming ? 'À venir · ' : ($isCompleted ? 'Effectuée · ' : ($isPlanned ? 'Prévu · ' : '')) ?><?= e(activity_label((string) $event['type'])) ?></span>
</a>
