<x-app-layout>
    <x-slot name="header">{{ __('Sessões') }}</x-slot>

    @php
        $prev = $month->copy()->subMonth()->format('Y-m');
        $next = $month->copy()->addMonth()->format('Y-m');
        $navQuery = request()->except(['page', 'per_page']);
        $exportQuery = $navQuery;
    @endphp

    <div class="mx-auto max-w-7xl space-y-8">
        <x-page-hero :title="__('Sessões')" :subtitle="__('Consultas e acompanhamentos — calendário mensal, relatórios e lista filtrada.')" icon="clock">
            <x-slot name="actions">
                <a
                    href="{{ route('therapy-sessions.create', ['month' => $month->format('Y-m')]) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/20 transition hover:from-violet-500 hover:to-indigo-500"
                >
                    <x-ui.icon name="plus" class="h-5 w-5" />
                    {{ __('Agendar sessão') }}
                </a>
            </x-slot>
        </x-page-hero>

        <x-sessions-analytics-panel
            :form-action="route('therapy-sessions.index')"
            :filters-active="$filtersActive"
            :patients="$patients"
            :stats="$stats"
            :month="$month"
            :pdf-export-route="route('therapy-sessions.export.pdf', $exportQuery)"
            :excel-export-route="route('therapy-sessions.export.excel', $exportQuery)"
        />

        {{-- Calendário mensal (mesma grelha que a agenda) --}}
        <section aria-labelledby="sessions-calendar-heading" class="space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <h2 id="sessions-calendar-heading" class="text-lg font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Calendário') }}</h2>
                <a href="{{ route('schedule.index', array_merge($navQuery, ['month' => $month->format('Y-m')])) }}" class="text-sm font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400">{{ __('Abrir agenda completa') }} →</a>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-soft dark:border-slate-700 dark:bg-slate-900/80">
                <a href="{{ route('therapy-sessions.index', array_merge($navQuery, ['month' => $prev])) }}" class="text-sm font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400">← {{ __('Mês anterior') }}</a>
                <span class="text-lg font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $month->translatedFormat('F Y') }}</span>
                <a href="{{ route('therapy-sessions.index', array_merge($navQuery, ['month' => $next])) }}" class="text-sm font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400">{{ __('Próximo mês') }} →</a>
            </div>

            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-xs dark:border-slate-700 dark:bg-slate-900/50">
                <span class="font-semibold text-slate-600 dark:text-slate-300">{{ __('Legenda') }}:</span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-violet-50 px-2 py-1 font-medium text-violet-900 ring-1 ring-violet-600/10 dark:bg-violet-950/50 dark:text-violet-100">{{ __('Agendada') }}</span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2 py-1 font-medium text-emerald-900 ring-1 ring-emerald-600/15 dark:bg-emerald-950/50 dark:text-emerald-100">{{ __('Concluída') }}</span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-2 py-1 font-medium text-rose-800 line-through ring-1 ring-rose-600/15 dark:bg-rose-950/40 dark:text-rose-200">{{ __('Cancelada') }}</span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2 py-1 font-medium text-amber-900 ring-1 ring-amber-600/10 dark:bg-amber-950/40 dark:text-amber-100">{{ __('Bloqueio') }}</span>
            </div>

            <x-ui.table-card class="!overflow-visible">
                <table class="min-w-full border-collapse text-center text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-gradient-to-r from-slate-100 via-violet-50 to-indigo-50 dark:border-slate-700 dark:from-slate-800 dark:via-slate-800 dark:to-slate-800">
                            @foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $d)
                                <th class="px-1 py-2.5 font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200 sm:px-2">{{ $d }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($weeks as $week)
                            <tr class="border-b border-slate-100 dark:border-slate-700">
                                @foreach ($week as $cell)
                                    @php
                                        $cellDate = $cell['date']->format('Y-m-d');
                                        $scheduleUrl = route('therapy-sessions.create', array_filter([
                                            'date' => $cellDate,
                                            'month' => $month->format('Y-m'),
                                        ]));
                                        $isToday = $cell['date']->isToday();
                                    @endphp
                                    <td class="relative min-h-[4.5rem] overflow-visible align-top px-1 py-2 text-left sm:min-h-[5.5rem] sm:px-2 sm:py-3 {{ $cell['in_month'] ? 'bg-white dark:bg-slate-900/50' : 'bg-slate-50/80 text-slate-400 dark:bg-slate-950/50 dark:text-slate-500' }}">
                                        <a
                                            href="{{ $scheduleUrl }}"
                                            class="absolute inset-0 z-0 rounded-lg ring-inset transition hover:bg-violet-50/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 dark:hover:bg-violet-950/20 dark:focus-visible:ring-violet-400"
                                            aria-label="{{ __('Agendar sessão em :date', ['date' => $cell['date']->format('d/m/Y')]) }}"
                                        ></a>
                                        <div class="relative z-10 flex min-h-[4rem] flex-col gap-0.5 text-left pointer-events-none sm:min-h-[5rem]">
                                            <span
                                                class="inline-flex h-6 min-w-[1.25rem] items-center justify-center self-start rounded-md px-1 text-xs font-semibold tabular-nums sm:text-sm {{ $isToday ? 'bg-blue-600 text-white shadow-sm dark:bg-blue-500' : ($cell['in_month'] ? 'text-slate-900 dark:text-slate-100' : '') }}"
                                            >
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
        </section>

        {{-- Lista paginada --}}
        <section aria-labelledby="sessions-list-heading" class="space-y-4">
            <h2 id="sessions-list-heading" class="text-lg font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Lista de sessões') }}</h2>

            <x-ui.table-card class="!overflow-visible">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-gradient-to-r from-slate-100 via-violet-50 to-indigo-50 dark:from-slate-800 dark:via-slate-800 dark:to-slate-800">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">{{ __('Data e hora') }}</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">{{ __('Paciente') }}</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">{{ __('Tipo') }}</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">{{ __('Status') }}</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">{{ __('Pagamento') }}</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">{{ __('Ações') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-900/50">
                        @forelse ($sessions as $session)
                            @php
                                $badgeVariant = match ($session->status) {
                                    \App\Enums\TherapySessionStatus::Completed => 'success',
                                    \App\Enums\TherapySessionStatus::Scheduled => 'info',
                                    \App\Enums\TherapySessionStatus::Cancelled => 'danger',
                                };
                                $timeStr = is_string($session->session_time)
                                    ? substr($session->session_time, 0, 5)
                                    : $session->session_time->format('H:i');
                            @endphp
                            <tr class="group transition hover:bg-violet-50/70 dark:hover:bg-slate-800/80">
                                <td class="px-5 py-4 text-sm text-slate-900 dark:text-slate-100">
                                    <span class="font-medium">{{ $session->session_date->format('d/m/Y') }}</span>
                                    <span class="text-slate-400 dark:text-slate-500">·</span>
                                    <span class="text-slate-600 dark:text-slate-300">{{ $timeStr }}</span>
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-slate-900 dark:text-slate-100">{{ $session->patient?->name ?? ($session->session_mode === \App\Enums\SessionMode::WithObserver ? __('Escuta / supervisão') : '—') }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-300">{{ $session->type->label() }}</td>
                                <td class="px-5 py-4">
                                    <x-ui.badge :variant="$badgeVariant">{{ $session->status->label() }}</x-ui.badge>
                                </td>
                                <td class="px-5 py-4">
                                    <x-therapy-session-payment-cell :session="$session" />
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <x-therapy-session-row-actions :session="$session" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('Nenhuma sessão.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>

            <x-list-pagination :paginator="$sessions" :item-label="__('sessões')" />
        </section>
    </div>
</x-app-layout>
