@props([
    'paginator',
    'itemLabel' => __('registros'),
    'perPageOptions' => [10, 15, 25, 50],
])

@php
    $query = request()->except(['page', 'per_page']);
    $currentPerPage = (int) request('per_page', $paginator->perPage());
@endphp

@if ($paginator->total() > 0)
    <section
        {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80 dark:border-slate-700 dark:bg-slate-900/70 dark:ring-slate-700/50']) }}
        aria-label="{{ __('Paginação da lista') }}"
    >
        <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:p-5">
            <div class="min-w-0 text-center sm:text-left">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    @if ($paginator->firstItem())
                        {{ __('A mostrar :from–:to de :total :label', [
                            'from' => $paginator->firstItem(),
                            'to' => $paginator->lastItem(),
                            'total' => $paginator->total(),
                            'label' => $itemLabel,
                        ]) }}
                    @else
                        {{ __(':total :label', ['total' => $paginator->total(), 'label' => $itemLabel]) }}
                    @endif
                </p>
                @if ($paginator->hasPages())
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                        {{ __('Página :current de :last', ['current' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}
                    </p>
                @endif
            </div>

            <form method="get" class="flex shrink-0 items-center justify-center gap-2 sm:justify-end">
                @foreach ($query as $key => $value)
                    @if (is_array($value))
                        @foreach ($value as $nestedKey => $nestedValue)
                            <input type="hidden" name="{{ $key }}[{{ $nestedKey }}]" value="{{ $nestedValue }}" />
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
                    @endif
                @endforeach

                <label for="per_page_{{ $paginator->currentPage() }}" class="sr-only">{{ __('Itens por página') }}</label>
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Por página') }}</span>
                <select
                    id="per_page_{{ $paginator->currentPage() }}"
                    name="per_page"
                    onchange="this.form.submit()"
                    class="h-9 rounded-xl border-0 bg-slate-50 py-1.5 pl-3 pr-8 text-sm font-medium text-slate-700 ring-1 ring-slate-200 focus:outline-none focus:ring-2 focus:ring-violet-500/40 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600"
                >
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected($currentPerPage === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if ($paginator->hasPages())
            <div class="border-t border-slate-100 px-4 py-4 dark:border-slate-800 sm:px-5">
                {{ $paginator->onEachSide(1)->links('pagination.psiconecta') }}
            </div>
        @endif
    </section>
@endif
