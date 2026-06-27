@props(['active' => false, 'href'])

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'rounded-lg px-3 py-2 text-sm font-semibold transition',
        'bg-white text-emerald-800 shadow-sm dark:bg-slate-700 dark:text-emerald-300' => $active,
        'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' => ! $active,
    ]) }}
>{{ $slot }}</a>
