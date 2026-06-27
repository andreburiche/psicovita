@props([
    'icon',
    'title',
    'subtitle' => null,
    'iconTone' => 'violet',
])

@php
    $tones = [
        'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
        'indigo' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
        'sky' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
        'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
        'rose' => 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
        'teal' => 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300',
        'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    ];
    $iconClass = $tones[$iconTone] ?? $tones['violet'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-3']) }}>
    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $iconClass }}" aria-hidden="true">
        <x-ui.icon :name="$icon" class="h-5 w-5" />
    </span>
    <div class="min-w-0 pt-0.5">
        <h2 class="text-base font-bold tracking-tight text-slate-900 dark:text-white">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ $subtitle }}</p>
        @endif
    </div>
</div>
