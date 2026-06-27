@props([
    'variant' => 'neutral',
])

@php
    $variants = [
        'neutral' => 'bg-slate-100 text-slate-700 ring-slate-600/10',
        'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/15',
        'warning' => 'bg-amber-50 text-amber-900 ring-amber-600/15',
        'danger' => 'bg-rose-50 text-rose-800 ring-rose-600/15',
        'info' => 'bg-sky-50 text-sky-800 ring-sky-600/15',
        'violet' => 'bg-violet-50 text-violet-800 ring-violet-600/15',
    ];
    $classes = $variants[$variant] ?? $variants['neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide ring-1 ring-inset '.$classes]) }}>
    {{ $slot }}
</span>
