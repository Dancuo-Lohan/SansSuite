<?php
/**
 * Shared variables created by Support\ViewResponse::render().
 *
 * @var string $__corianderRequestedView Requested view path.
 * @var string|null $metadata Page metadata supplied by the controller or metadata file.
 * @var string|null $lang Optional document language supplied by the metadata file.
 */
$requestedView = isset($__corianderRequestedView) ? $__corianderRequestedView : 'home';
$isHomeView = $requestedView === 'home';
$isApplicationsView = str_starts_with($requestedView, 'applications');
$isCalendarView = $requestedView === 'calendar';
$metaDataPath = PROJECT_ROOT . '/public/public_views/' . $requestedView . '/metadata.php';
if (file_exists($metaDataPath)) {
    require_once $metaDataPath;
}
?>
<!DOCTYPE html>
<html lang="<?= isset($lang) ? $lang : 'fr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $metadata ?? '<title>Sans Suite</title><meta name="description" content="Suivi local de candidatures.">' ?>
    <link rel="stylesheet" href="<?= \CorianderCore\Core\Support\PublicUrl::versionedAsset('assets/css/output.css') ?>">
</head>
<body class="flex min-h-screen w-full flex-col bg-white pb-16 text-black sm:pb-0">
    <!-- Main navigation -->
    <header class="sticky top-0 z-50 border-b border-slate/15 bg-true-white/95 backdrop-blur">
        <nav class="mx-auto flex min-h-16 w-full max-w-7xl items-center justify-between gap-5 px-4 py-3 sm:px-6 lg:px-8" aria-label="Navigation principale">
            <a href="/home" class="font-concert-one text-2xl tracking-1 text-brand-dark">Sans Suite</a>
            <div class="hidden items-center gap-3 text-sm font-semibold text-slate sm:flex">
                <a href="/home" class="whitespace-nowrap rounded-lg px-3 py-2 <?= $isHomeView ? 'bg-brand-soft text-brand-dark' : 'hover:bg-white hover:text-brand-dark' ?>" <?= $isHomeView ? 'aria-current="page"' : '' ?>>Vue d'ensemble</a>
                <a href="/applications" class="whitespace-nowrap rounded-lg px-3 py-2 <?= $isApplicationsView ? 'bg-brand-soft text-brand-dark' : 'hover:bg-white hover:text-brand-dark' ?>" <?= $isApplicationsView ? 'aria-current="page"' : '' ?>>Candidatures</a>
                <a href="/calendar" class="whitespace-nowrap rounded-lg px-3 py-2 <?= $isCalendarView ? 'bg-brand-soft text-brand-dark' : 'hover:bg-white hover:text-brand-dark' ?>" <?= $isCalendarView ? 'aria-current="page"' : '' ?>>Calendrier</a>
            </div>
        </nav>
    </header>
    <!-- Mobile navigation -->
    <nav class="fixed inset-x-0 bottom-0 z-50 grid grid-cols-3 border-t border-slate/15 bg-true-white/95 px-2 py-2 text-center text-xs font-semibold text-slate shadow-[0_-8px_24px_rgba(35,74,115,0.08)] backdrop-blur sm:hidden" aria-label="Navigation mobile principale">
        <a href="/home" class="rounded-lg px-2 py-2 <?= $isHomeView ? 'bg-brand-soft text-brand-dark' : 'hover:bg-slate-50 hover:text-brand-dark' ?>" <?= $isHomeView ? 'aria-current="page"' : '' ?>>Vue d'ensemble</a>
        <a href="/applications" class="rounded-lg px-2 py-2 <?= $isApplicationsView ? 'bg-brand-soft text-brand-dark' : 'hover:bg-slate-50 hover:text-brand-dark' ?>" <?= $isApplicationsView ? 'aria-current="page"' : '' ?>>Candidatures</a>
        <a href="/calendar" class="rounded-lg px-2 py-2 <?= $isCalendarView ? 'bg-brand-soft text-brand-dark' : 'hover:bg-slate-50 hover:text-brand-dark' ?>" <?= $isCalendarView ? 'aria-current="page"' : '' ?>>Calendrier</a>
    </nav>
    <main class="relative mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
