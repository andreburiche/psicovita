<x-app-layout>
    <x-slot name="header">{{ __('Agenda') }}</x-slot>

    @php
        $prev = $month->copy()->subMonth()->format('Y-m');
        $next = $month->copy()->addMonth()->format('Y-m');
        $navQuery = request()->except(['page', 'per_page']);
        $exportQuery = $navQuery;
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-hero :title="__('Agenda')" :subtitle="__('Vista mensal de sessões e bloqueios com relatórios e filtros analíticos.')" icon="calendar">
            <x-slot name="actions">
                <a
                    href="{{ route('schedule-blocks.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                >
                    <x-ui.icon name="ban" class="h-4 w-4 text-amber-600" />
                    {{ __('Novo bloqueio') }}
                </a>
                <a
                    href="{{ route('therapy-sessions.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/20 transition hover:from-violet-500 hover:to-indigo-500"
                >
                    <x-ui.icon name="plus" class="h-4 w-4" />
                    {{ __('Nova sessão') }}
                </a>
            </x-slot>
        </x-page-hero>

        <x-sessions-analytics-panel
            :form-action="route('schedule.index')"
            :filters-active="$filtersActive"
            :patients="$patients"
            :stats="$stats"
            :month="$month"
            :pdf-export-route="route('schedule.export.pdf', $exportQuery)"
            :excel-export-route="route('schedule.export.excel', $exportQuery)"
        />

        <nav class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-soft dark:border-slate-700 dark:bg-slate-900/80" aria-label="{{ __('Navegação mensal da agenda') }}">
            <a href="{{ route('schedule.index', array_merge($navQuery, ['month' => $prev])) }}" class="text-sm font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400">← {{ __('Mês anterior') }}</a>
            <h2 class="text-lg font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $month->translatedFormat('F Y') }}</h2>
            <a href="{{ route('schedule.index', array_merge($navQuery, ['month' => $next])) }}" class="text-sm font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400">{{ __('Próximo mês') }} →</a>
        </nav>

        @php
            $sessionSummary = collect($weeks)
                ->flatten(1)
                ->filter(fn ($cell) => $cell['in_month'] && $cell['sessions']->isNotEmpty())
                ->flatMap(fn ($cell) => $cell['sessions']->map(fn ($s) => [
                    'date' => $cell['date']->format('d/m/Y'),
                    'time' => substr((string) $s->session_time, 0, 5),
                    'patient' => $s->displayLabel(),
                    'status' => $s->status->label(),
                ]));
        @endphp

        @if ($sessionSummary->isNotEmpty())
            <div class="sr-only" id="schedule-month-summary">
                <h3>{{ __('Resumo de sessões em :month', ['month' => $month->translatedFormat('F Y')]) }}</h3>
                <ul>
                    @foreach ($sessionSummary as $item)
                        <li>{{ $item['date'] }} {{ $item['time'] }} — {{ $item['patient'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-xs dark:border-slate-700 dark:bg-slate-900/50">
            <span class="font-semibold text-slate-600 dark:text-slate-300">{{ __('Legenda') }}:</span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-violet-50 px-2 py-1 font-medium text-violet-900 ring-1 ring-violet-600/10 dark:bg-violet-950/50 dark:text-violet-100">{{ __('Agendada') }}</span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2 py-1 font-medium text-emerald-900 ring-1 ring-emerald-600/15 dark:bg-emerald-950/50 dark:text-emerald-100">{{ __('Concluída') }}</span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-2 py-1 font-medium text-rose-800 line-through ring-1 ring-rose-600/15 dark:bg-rose-950/40 dark:text-rose-200">{{ __('Cancelada') }}</span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2 py-1 font-medium text-amber-900 ring-1 ring-amber-600/10 dark:bg-amber-950/40 dark:text-amber-100">{{ __('Bloqueio') }}</span>
        </div>

        <x-ui.table-card class="!overflow-visible">
            <table class="min-w-full border-collapse text-center text-xs sm:text-sm" aria-describedby="{{ $sessionSummary->isNotEmpty() ? 'schedule-month-summary' : '' }}">
                <caption class="sr-only">{{ __('Calendário mensal de sessões e bloqueios') }} — {{ $month->translatedFormat('F Y') }}</caption>
                <thead>
                    <tr class="border-b border-slate-100 bg-gradient-to-r from-slate-100 via-violet-50 to-indigo-50 dark:border-slate-700 dark:from-slate-800 dark:via-slate-800 dark:to-slate-800">
                        @foreach ([__('Seg'), __('Ter'), __('Qua'), __('Qui'), __('Sex'), __('Sáb'), __('Dom')] as $d)
                            <th scope="col" class="px-1 py-2.5 font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200 sm:px-2">{{ $d }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($weeks as $week)
                        <tr class="border-b border-slate-100 dark:border-slate-700">
                            @foreach ($week as $cell)
                                <td class="relative min-h-[4.5rem] overflow-visible align-top px-1 py-2 text-left sm:min-h-[5.5rem] sm:px-2 sm:py-3 {{ $cell['in_month'] ? 'bg-white dark:bg-slate-900/50' : 'bg-slate-50/80 text-slate-400 dark:bg-slate-950/50 dark:text-slate-500' }}">
                                    <div class="relative z-10 flex min-h-[4rem] flex-col gap-0.5 text-left sm:min-h-[5rem]">
                                        <span class="inline-flex h-6 min-w-[1.25rem] items-center justify-center self-start rounded-md px-1 text-xs font-semibold tabular-nums sm:text-sm {{ $cell['date']->isToday() ? 'bg-blue-600 text-white shadow-sm dark:bg-blue-500' : ($cell['in_month'] ? 'text-slate-900 dark:text-slate-100' : '') }}">
                                            {{ $cell['date']->format('j') }}
                                        </span>
                                    @if ($cell['blocks']->isNotEmpty())
                                        <div class="mt-0.5 space-y-0.5">
                                            @foreach ($cell['blocks'] as $b)
                                                <div class="rounded-lg bg-amber-50 px-1 py-0.5 text-[10px] font-medium text-amber-900 ring-1 ring-amber-600/10 dark:bg-amber-950/40 dark:text-amber-100 sm:text-xs">{{ __('Bloqueio') }} {{ substr((string) $b->start_time, 0, 5) }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if ($cell['sessions']->isNotEmpty())
                                        <ul class="mt-0.5 space-y-0.5">
                                            @foreach ($cell['sessions'] as $s)
                                                <x-therapy-session-chip :session="$s" />
                                            @endforeach
                                        </ul>
                                    @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.table-card>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-soft">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Bloqueios do mês') }}</h3>
                <a href="{{ route('schedule-blocks.index') }}" class="text-sm font-semibold text-violet-600 transition hover:text-violet-500">{{ __('Gerir todos') }}</a>
            </div>
            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($blocks as $block)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                        <span class="font-medium text-slate-900">{{ $block->block_date->format('d/m/Y') }}</span>
                        <span class="text-slate-600">{{ substr((string) $block->start_time, 0, 5) }} — {{ substr((string) $block->end_time, 0, 5) }}</span>
                        <span class="text-slate-500">{{ $block->reason ?? '—' }}</span>
                        <a href="{{ route('schedule-blocks.edit', $block) }}" class="font-semibold text-violet-600 transition hover:text-violet-500">{{ __('Editar') }}</a>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-slate-500">{{ __('Sem bloqueios neste mês.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
