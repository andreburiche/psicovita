@props([
    'label',
    'count' => 0,
    'icon' => 'banknote',
    'tone' => 'violet',
    'active' => false,
    'href' => '#',
])

@php
    $tones = [
        'violet' => [
            'icon' => 'bg-violet-50 text-violet-600 group-hover:bg-violet-100 dark:bg-violet-950/50 dark:text-violet-300 dark:group-hover:bg-violet-900/50',
            'value' => 'text-violet-700 dark:text-violet-300',
            'ring' => 'ring-violet-300 dark:ring-violet-500',
            'accent' => 'from-violet-500/10 to-transparent dark:from-violet-500/20',
        ],
        'sky' => [
            'icon' => 'bg-sky-50 text-sky-600 group-hover:bg-sky-100 dark:bg-sky-950/50 dark:text-sky-300 dark:group-hover:bg-sky-900/50',
            'value' => 'text-sky-700 dark:text-sky-300',
            'ring' => 'ring-sky-300 dark:ring-sky-500',
            'accent' => 'from-sky-500/10 to-transparent dark:from-sky-500/20',
        ],
        'emerald' => [
            'icon' => 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 dark:group-hover:bg-emerald-900/50',
            'value' => 'text-emerald-700 dark:text-emerald-300',
            'ring' => 'ring-emerald-300 dark:ring-emerald-500',
            'accent' => 'from-emerald-500/10 to-transparent dark:from-emerald-500/20',
        ],
        'amber' => [
            'icon' => 'bg-amber-50 text-amber-700 group-hover:bg-amber-100 dark:bg-amber-950/50 dark:text-amber-300 dark:group-hover:bg-amber-900/50',
            'value' => 'text-amber-800 dark:text-amber-300',
            'ring' => 'ring-amber-300 dark:ring-amber-500',
            'accent' => 'from-amber-500/10 to-transparent dark:from-amber-500/20',
        ],
        'slate' => [
            'icon' => 'bg-slate-100 text-slate-600 group-hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:group-hover:bg-slate-700',
            'value' => 'text-slate-800 dark:text-slate-200',
            'ring' => 'ring-slate-300 dark:ring-slate-500',
            'accent' => 'from-slate-500/10 to-transparent dark:from-slate-500/20',
        ],
        'rose' => [
            'icon' => 'bg-rose-50 text-rose-600 group-hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-300 dark:group-hover:bg-rose-900/50',
            'value' => 'text-rose-700 dark:text-rose-300',
            'ring' => 'ring-rose-300 dark:ring-rose-500',
            'accent' => 'from-rose-500/10 to-transparent dark:from-rose-500/20',
        ],
    ];
    $palette = $tones[$tone] ?? $tones['violet'];
@endphp

<a
    href="{{ $href }}"
    @class([
        'group relative overflow-hidden rounded-2xl border bg-white p-5 shadow-lg transition duration-200',
        'hover:-translate-y-0.5 hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-500',
        'border-slate-200 ring-1 ring-slate-100/80 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-slate-800/80',
        'ring-2 shadow-xl '.$palette['ring'] => $active,
    ])
    @if ($active) aria-current="true" @endif
>
    <div @class(['pointer-events-none absolute inset-0 bg-gradient-to-br opacity-100', $palette['accent']]) aria-hidden="true"></div>

    <div class="relative flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p @class(['mt-2 text-3xl font-bold tabular-nums tracking-tight', $palette['value']])>{{ $count }}</p>
            <p class="mt-2 text-xs font-medium text-slate-400 opacity-0 transition group-hover:opacity-100 dark:text-slate-500">
                {{ $active ? __('Filtro activo') : __('Clique para filtrar') }}
            </p>
        </div>
        <span @class(['relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition', $palette['icon']])>
            <x-ui.icon :name="$icon" class="h-6 w-6" />
        </span>
    </div>
</a>
