@props([
    'pdfRoute' => null,
    'excelRoute' => null,
])

@if ($pdfRoute || $excelRoute)
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
        @if ($pdfRoute)
            <a
                href="{{ $pdfRoute }}"
                class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-bold text-rose-800 shadow-sm transition hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200 dark:hover:bg-rose-950/60"
            >
                <x-ui.icon name="document-text" class="h-4 w-4 shrink-0" />
                {{ __('Exportar PDF') }}
            </a>
        @endif
        @if ($excelRoute)
            <a
                href="{{ $excelRoute }}"
                class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-800 shadow-sm transition hover:bg-emerald-100 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-950/60"
            >
                <x-ui.icon name="chart-bar" class="h-4 w-4 shrink-0" />
                {{ __('Exportar Excel') }}
            </a>
        @endif
    </div>
@endif
