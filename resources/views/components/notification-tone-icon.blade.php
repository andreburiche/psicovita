@props([
    'tone' => 'system',
    'compact' => false,
])

@php
    $iconClass = match ($tone) {
        'message' => 'text-violet-500 dark:text-violet-400',
        'payment' => 'text-emerald-500 dark:text-emerald-400',
        'subscription' => 'text-amber-500 dark:text-amber-400',
        'invite' => 'text-sky-500 dark:text-sky-400',
        'whatsapp' => 'text-green-500 dark:text-green-400',
        default => 'text-slate-400 dark:text-slate-500',
    };

    $boxClass = match ($tone) {
        'message' => 'bg-violet-100 text-violet-600 dark:bg-violet-950/50 dark:text-violet-400',
        'payment' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400',
        'subscription' => 'bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400',
        'invite' => 'bg-sky-100 text-sky-600 dark:bg-sky-950/50 dark:text-sky-400',
        'whatsapp' => 'bg-green-100 text-green-600 dark:bg-green-950/50 dark:text-green-400',
        default => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    };
@endphp

@if ($compact)
    <span @class(['mt-0.5 shrink-0', $iconClass])>
        {{ $slot }}
    </span>
@else
    <span @class(['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', $boxClass])>
        {{ $slot }}
    </span>
@endif
