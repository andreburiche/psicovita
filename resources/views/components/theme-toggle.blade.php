@props([
    'variant' => 'toolbar',
])

@php
    $base =
        'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border shadow-sm transition focus:outline-none focus:ring-2 focus:ring-violet-500/40 ';
    $toolbar =
        'border-slate-300/80 bg-white text-amber-500 hover:border-amber-400/80 hover:bg-amber-50 dark:border-slate-600 dark:bg-slate-800 dark:text-amber-400 dark:hover:bg-slate-700 ';
    $fixed = 'fixed right-4 top-4 z-[100] ' . $toolbar;
    $classes = $base . ($variant === 'fixed' ? $fixed : $toolbar);
@endphp

<button type="button" class="{{ $classes }}" onclick="window.psiToggleTheme()" title="{{ __('Alternar tema claro/escuro') }}" aria-label="{{ __('Alternar tema claro/escuro') }}">
    <span class="block dark:hidden" aria-hidden="true">
        <x-ui.icon name="sun" class="h-5 w-5" />
    </span>
    <span class="hidden dark:block" aria-hidden="true">
        <x-ui.icon name="moon" class="h-5 w-5" />
    </span>
</button>
