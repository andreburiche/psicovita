@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Navegação de páginas') }}">
        <div class="flex items-center justify-between gap-3 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-400 dark:border-slate-600 dark:bg-slate-800">
                    <x-ui.icon name="chevron-left" class="h-4 w-4" />
                    {{ __('Anterior') }}
                </span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-violet-700 shadow-sm transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:text-violet-300 dark:hover:border-violet-500/40 dark:hover:bg-violet-950/40"
                >
                    <x-ui.icon name="chevron-left" class="h-4 w-4" />
                    {{ __('Anterior') }}
                </a>
            @endif

            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-violet-700 shadow-sm transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:text-violet-300 dark:hover:border-violet-500/40 dark:hover:bg-violet-950/40"
                >
                    {{ __('Próxima') }}
                    <x-ui.icon name="chevron-right" class="h-4 w-4" />
                </a>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-400 dark:border-slate-600 dark:bg-slate-800">
                    {{ __('Próxima') }}
                    <x-ui.icon name="chevron-right" class="h-4 w-4" />
                </span>
            @endif
        </div>

        <div class="hidden sm:flex sm:items-center sm:justify-center">
            <span class="inline-flex -space-x-px rounded-xl shadow-sm ring-1 ring-slate-200/80 dark:ring-slate-700">
                @if ($paginator->onFirstPage())
                    <span
                        aria-disabled="true"
                        aria-label="{{ __('Página anterior') }}"
                        class="inline-flex items-center rounded-l-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-600"
                    >
                        <x-ui.icon name="chevron-left" class="h-5 w-5" />
                    </span>
                @else
                    <a
                        href="{{ $paginator->previousPageUrl() }}"
                        rel="prev"
                        aria-label="{{ __('Página anterior') }}"
                        class="inline-flex items-center rounded-l-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-violet-50 hover:text-violet-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-violet-950/40 dark:hover:text-violet-300"
                    >
                        <x-ui.icon name="chevron-left" class="h-5 w-5" />
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span
                            aria-disabled="true"
                            class="inline-flex items-center border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400"
                        >
                            {{ $element }}
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">
                                    <span class="inline-flex items-center border border-violet-300 bg-violet-600 px-4 py-2 text-sm font-semibold text-white dark:border-violet-500 dark:bg-violet-600">
                                        {{ $page }}
                                    </span>
                                </span>
                            @else
                                <a
                                    href="{{ $url }}"
                                    aria-label="{{ __('Ir para a página :page', ['page' => $page]) }}"
                                    class="inline-flex items-center border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-violet-50 hover:text-violet-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-violet-950/40 dark:hover:text-violet-300"
                                >
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a
                        href="{{ $paginator->nextPageUrl() }}"
                        rel="next"
                        aria-label="{{ __('Próxima página') }}"
                        class="inline-flex items-center rounded-r-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-violet-50 hover:text-violet-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-violet-950/40 dark:hover:text-violet-300"
                    >
                        <x-ui.icon name="chevron-right" class="h-5 w-5" />
                    </a>
                @else
                    <span
                        aria-disabled="true"
                        aria-label="{{ __('Próxima página') }}"
                        class="inline-flex items-center rounded-r-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-600"
                    >
                        <x-ui.icon name="chevron-right" class="h-5 w-5" />
                    </span>
                @endif
            </span>
        </div>
    </nav>
@endif
