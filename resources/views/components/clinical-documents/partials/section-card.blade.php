@props([
    'title',
    'description' => null,
    'icon' => 'document-text',
    'tone' => 'violet',
])

@php
    $tones = [
        'violet' => [
            'header' => 'from-violet-50/90 to-indigo-50/50 dark:from-violet-950/40 dark:to-indigo-950/20',
            'icon' => 'bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-400',
        ],
        'indigo' => [
            'header' => 'from-indigo-50/90 to-sky-50/50 dark:from-indigo-950/40 dark:to-sky-950/20',
            'icon' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400',
        ],
        'teal' => [
            'header' => 'from-teal-50/90 to-emerald-50/50 dark:from-teal-950/40 dark:to-emerald-950/20',
            'icon' => 'bg-teal-100 text-teal-600 dark:bg-teal-950 dark:text-teal-400',
        ],
        'slate' => [
            'header' => 'from-slate-50 to-white dark:from-slate-900 dark:to-slate-900/90',
            'icon' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        ],
    ];
    $palette = $tones[$tone] ?? $tones['violet'];
@endphp

<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60']) }}>
    <div class="border-b border-slate-100 bg-gradient-to-r px-5 py-4 dark:border-slate-700 {{ $palette['header'] }}">
        <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $palette['icon'] }}" aria-hidden="true">
                <x-ui.icon :name="$icon" class="h-4 w-4" />
            </span>
            {{ $title }}
        </h3>
        @if ($description)
            <p class="mt-1 pl-9 text-xs text-slate-500 dark:text-slate-400">{{ $description }}</p>
        @endif
    </div>
    <div class="p-5 sm:p-6">
        {{ $slot }}
    </div>
</section>
