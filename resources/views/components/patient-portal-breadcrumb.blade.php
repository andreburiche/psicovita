@props(['items' => []])

@if (count($items) > 0)
    <nav aria-label="{{ __('Navegação') }}" class="flex flex-wrap items-center gap-1.5 text-sm">
        @foreach ($items as $index => $item)
            @if ($index > 0)
                <x-ui.icon name="chevron-right" class="h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-slate-500" aria-hidden="true" />
            @endif

            @if ($loop->last || empty($item['href']))
                <span class="truncate font-semibold text-slate-900 dark:text-white" @if ($loop->last) aria-current="page" @endif>
                    {{ $item['label'] }}
                </span>
            @else
                <a
                    href="{{ $item['href'] }}"
                    class="truncate font-medium text-slate-500 transition hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400"
                >
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </nav>
@endif
