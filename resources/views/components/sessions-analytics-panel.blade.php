@props([
    'formAction',
    'filtersActive' => false,
    'patients',
    'stats',
    'month' => null,
    'pdfExportRoute' => null,
    'excelExportRoute' => null,
])

<div class="space-y-6">
    {{-- Relatório / métricas --}}
    <section aria-labelledby="sessions-stats-heading" class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/40 sm:p-8">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="sessions-stats-heading" class="text-base font-bold text-slate-900 dark:text-slate-100">{{ __('Relatório') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Período: :period', ['period' => $stats['period_label']]) }}
                </p>
            </div>

            @if ($pdfExportRoute || $excelExportRoute)
                <div class="flex flex-wrap items-center gap-2">
                    @if ($pdfExportRoute)
                        <a
                            href="{{ $pdfExportRoute }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800 transition hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200 dark:hover:bg-rose-950/60"
                        >
                            <x-ui.icon name="document-text" class="h-4 w-4 shrink-0" />
                            {{ __('Exportar PDF') }}
                        </a>
                    @endif
                    @if ($excelExportRoute)
                        <a
                            href="{{ $excelExportRoute }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-950/60"
                        >
                            <x-ui.icon name="chart-bar" class="h-4 w-4 shrink-0" />
                            {{ __('Exportar Excel') }}
                        </a>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
            @foreach ([
                ['label' => __('Total'), 'value' => $stats['total'], 'tone' => 'slate'],
                ['label' => __('Agendadas'), 'value' => $stats['scheduled'], 'tone' => 'sky'],
                ['label' => __('Concluídas'), 'value' => $stats['completed'], 'suffix' => $stats['total'] > 0 ? $stats['completion_rate'].'%' : null, 'tone' => 'emerald'],
                ['label' => __('Canceladas'), 'value' => $stats['cancelled'], 'suffix' => $stats['total'] > 0 ? $stats['cancellation_rate'].'%' : null, 'tone' => 'rose'],
                ['label' => __('Online'), 'value' => $stats['online'], 'tone' => 'violet'],
                ['label' => __('Presencial'), 'value' => $stats['in_person'], 'tone' => 'indigo'],
                ['label' => __('Pacientes'), 'value' => $stats['unique_patients'], 'tone' => 'teal'],
                ['label' => __('Bloqueios'), 'value' => $stats['blocks'], 'tone' => 'amber'],
            ] as $card)
                @php
                    $toneClasses = match ($card['tone']) {
                        'sky' => 'text-sky-700 dark:text-sky-300',
                        'emerald' => 'text-emerald-700 dark:text-emerald-300',
                        'rose' => 'text-rose-700 dark:text-rose-300',
                        'violet' => 'text-violet-700 dark:text-violet-300',
                        'indigo' => 'text-indigo-700 dark:text-indigo-300',
                        'teal' => 'text-teal-700 dark:text-teal-300',
                        'amber' => 'text-amber-700 dark:text-amber-300',
                        default => 'text-slate-900 dark:text-white',
                    };
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-600 dark:bg-slate-800/80">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums {{ $toneClasses }}">
                        {{ $card['value'] }}
                        @if (! empty($card['suffix']))
                            <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">{{ $card['suffix'] }}</span>
                        @endif
                    </p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Filtros --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-violet-50/20 to-indigo-50/30 p-1 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100/70 dark:border-slate-700/80 dark:from-slate-900 dark:via-slate-900 dark:to-violet-950/20 dark:ring-slate-700/50">
        <form method="get" action="{{ $formAction }}" class="space-y-4 p-4 sm:p-5">
            @if ($month)
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}" />
            @endif

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <x-ui.section-heading icon="filter" icon-tone="violet" :title="__('Filtros')" class="flex-1" />
                @if ($filtersActive)
                    <a href="{{ $formAction }}{{ $month ? '?month='.$month->format('Y-m') : '' }}" class="text-xs font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400">{{ __('Limpar filtros') }}</a>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                <div class="sm:col-span-1 lg:col-span-2">
                    <label for="filter-status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Status') }}</label>
                    <select
                        id="filter-status"
                        name="status"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-3 pr-8 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    >
                        <option value="">{{ __('Todos') }}</option>
                        @foreach (\App\Enums\TherapySessionStatus::cases() as $st)
                            <option value="{{ $st->value }}" @selected(request('status') === $st->value)>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-1 lg:col-span-2">
                    <label for="filter-type" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Tipo') }}</label>
                    <select
                        id="filter-type"
                        name="type"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-3 pr-8 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    >
                        <option value="">{{ __('Todos') }}</option>
                        @foreach (\App\Enums\TherapySessionType::cases() as $tp)
                            <option value="{{ $tp->value }}" @selected(request('type') === $tp->value)>{{ $tp->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-1 lg:col-span-3">
                    <label for="filter-patient" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Paciente') }}</label>
                    <select
                        id="filter-patient"
                        name="patient_id"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-3 pr-8 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    >
                        <option value="">{{ __('Todos') }}</option>
                        @foreach ($patients as $p)
                            <option value="{{ $p->id }}" @selected((string) request('patient_id') === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-5">
                    <label for="filter-q" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Buscar por nome do paciente') }}</label>
                    <input
                        id="filter-q"
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="{{ __('Nome…') }}"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    />
                </div>

                <div class="sm:col-span-1 lg:col-span-3">
                    <label for="filter-from" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Desde') }}</label>
                    <input
                        id="filter-from"
                        type="date"
                        name="from"
                        value="{{ request('from') }}"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    />
                </div>

                <div class="sm:col-span-1 lg:col-span-3">
                    <label for="filter-to" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Até') }}</label>
                    <input
                        id="filter-to"
                        type="date"
                        name="to"
                        value="{{ request('to') }}"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    />
                </div>

                <div class="sm:col-span-2 lg:col-span-6 lg:col-start-7 lg:flex lg:justify-end">
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/20 transition hover:from-violet-500 hover:to-indigo-500 lg:w-auto"
                    >
                        <x-ui.icon name="search" class="h-4 w-4" />
                        {{ __('Aplicar filtros') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
