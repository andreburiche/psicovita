@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'iconTone' => 'violet',
])

@php
    $heroIconTones = [
        'violet' => 'bg-gradient-to-br from-violet-500 to-indigo-600 text-white shadow-lg shadow-violet-500/30',
        'indigo' => 'bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-500/30',
        'teal' => 'bg-gradient-to-br from-teal-500 to-emerald-600 text-white shadow-lg shadow-teal-500/30',
        'amber' => 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/30',
    ];
    $heroIconClass = $heroIconTones[$iconTone] ?? $heroIconTones['violet'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="flex min-w-0 items-start gap-4">
        @if ($icon)
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $heroIconClass }}" aria-hidden="true">
                <x-ui.icon :name="$icon" class="h-7 w-7" />
            </span>
        @endif
        <div class="min-w-0">
        @isset($eyebrow)
            <p class="text-xs font-bold uppercase tracking-widest text-violet-600 dark:text-violet-400">{{ $eyebrow }}</p>
        @endisset
        <h2 class="bg-gradient-to-r from-slate-900 via-violet-800 to-indigo-800 bg-clip-text text-3xl font-extrabold tracking-tight text-transparent dark:from-white dark:via-violet-200 dark:to-indigo-200 sm:text-[2rem]">
            {{ $title }}
        </h2>
        @if ($subtitle)
            <p class="mt-2 max-w-2xl text-sm font-medium leading-relaxed text-slate-600 dark:text-slate-400">{{ $subtitle }}</p>
        @endif
        </div>
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
