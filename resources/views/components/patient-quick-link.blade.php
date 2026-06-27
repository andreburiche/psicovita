@props([
    'href',
    'icon',
    'title',
    'description',
    'tone' => 'emerald',
])

@php
    $tones = [
        'emerald' => 'border-emerald-200/80 hover:border-emerald-300 hover:bg-emerald-50/80 dark:border-emerald-800/50 dark:hover:border-emerald-700',
        'sky' => 'border-sky-200/80 hover:border-sky-300 hover:bg-sky-50/80 dark:border-sky-800/50 dark:hover:border-sky-700',
        'violet' => 'border-violet-200/80 hover:border-violet-300 hover:bg-violet-50/80 dark:border-violet-800/50 dark:hover:border-violet-700',
        'amber' => 'border-amber-200/80 hover:border-amber-300 hover:bg-amber-50/80 dark:border-amber-800/50 dark:hover:border-amber-700',
        'slate' => 'border-slate-200/80 hover:border-slate-300 hover:bg-slate-50/80 dark:border-slate-700 dark:hover:border-slate-600',
    ];
    $iconTones = [
        'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
        'sky' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300',
        'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300',
        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
        'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    ];
    $cardClass = $tones[$tone] ?? $tones['emerald'];
    $iconClass = $iconTones[$tone] ?? $iconTones['emerald'];
@endphp

<a
    href="{{ $href }}"
    class="group flex items-start gap-4 rounded-2xl border bg-white/95 p-5 shadow-sm ring-1 ring-slate-100/80 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-slate-900/80 dark:ring-slate-700/50 {{ $cardClass }}"
>
    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $iconClass }}" aria-hidden="true">
        <x-ui.icon :name="$icon" class="h-5 w-5" />
    </span>
    <div class="min-w-0 flex-1">
        <p class="font-bold text-slate-900 group-hover:text-emerald-800 dark:text-white dark:group-hover:text-emerald-300">{{ $title }}</p>
        <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ $description }}</p>
    </div>
    <x-ui.icon name="chevron-right" class="mt-1 h-4 w-4 shrink-0 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-emerald-600 dark:group-hover:text-emerald-400" />
</a>
