@props([
    'variant' => 'dropdown',
])

@php
    $dropdownClasses = 'block w-full px-4 py-2 text-start text-sm leading-5 text-slate-700 transition duration-150 ease-in-out hover:bg-violet-50 focus:bg-violet-50 focus:outline-none dark:text-slate-200 dark:hover:bg-slate-700/80 dark:focus:bg-slate-700/80';
    $inlineClasses = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-emerald-600 dark:hover:bg-emerald-950/50 dark:hover:text-emerald-200';
    $textClasses = 'rounded-md text-sm text-gray-600 underline transition hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800';
    $responsiveClasses = 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:border-gray-300 focus:bg-gray-50 focus:text-gray-800 focus:outline-none dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200 dark:focus:border-gray-600 dark:focus:bg-gray-700 dark:focus:text-gray-200';

    $linkClass = match ($variant) {
        'dropdown' => $dropdownClasses,
        'inline' => $inlineClasses,
        'text' => $textClasses,
        'responsive' => $responsiveClasses,
        default => $dropdownClasses,
    };
@endphp

<a
    href="{{ route('logout.confirm') }}"
    {{ $attributes->merge(['class' => $linkClass]) }}
    @if (in_array($variant, ['dropdown', 'responsive'], true))
        @click.stop
    @endif
    @if ($variant === 'dropdown') role="menuitem" @endif
>
    {{ $slot->isEmpty() ? __('Sair') : $slot }}
</a>
