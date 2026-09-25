<?php
declare(strict_types=1);

use Domain\ActivityType;
use Domain\ApplicationAge;
use Domain\ApplicationKind;
use Domain\ApplicationStatus;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $value, bool $withTime = false): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $date = new DateTimeImmutable($value);
        return $date->format($withTime ? 'd/m/Y à H:i' : 'd/m/Y');
    }
}

if (!function_exists('status_label')) {
    function status_label(string $status): string
    {
        return ApplicationStatus::label($status);
    }
}

if (!function_exists('kind_label')) {
    function kind_label(string $kind): string
    {
        return ApplicationKind::label($kind);
    }
}

if (!function_exists('activity_label')) {
    function activity_label(string $type): string
    {
        return ActivityType::label($type);
    }
}

if (!function_exists('status_classes')) {
    function status_classes(string $status): string
    {
        return match ($status) {
            'accepted', 'offer' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'rejected' => 'bg-red-50 text-red-800 border-red-200',
            'interview' => 'bg-violet-50 text-violet-800 border-violet-200',
            'no_response' => 'bg-slate-100 text-slate-700 border-slate-200',
            'contact' => 'bg-cyan-50 text-cyan-800 border-cyan-200',
            default => 'bg-brand-soft text-brand-dark border-blue-200',
        };
    }
}

if (!function_exists('application_age_label')) {
    function application_age_label(string $appliedAt): string
    {
        return ApplicationAge::label($appliedAt);
    }
}
