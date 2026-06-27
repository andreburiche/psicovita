<x-app-layout>
    <x-slot name="header">
        {{ __('Visão geral') }}
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
        <x-page-hero
            :title="__('Dashboard')"
            :subtitle="Auth::user()?->isProfessional()
                ? __('Resumo da sua prática — pacientes, sessões e indicadores do consultório.')
                : __('Área de conformidade e visão geral da plataforma.')"
            icon="dashboard"
        />

        @if (Auth::user()?->canManageLgpdRequests() && ! Auth::user()?->isProfessional())
            <section class="rounded-2xl border border-violet-200/80 bg-gradient-to-r from-violet-50/80 to-indigo-50/60 p-5 shadow-sm dark:border-violet-900/40 dark:from-violet-950/30 dark:to-indigo-950/20">
                <x-ui.section-heading
                    icon="shield-alert"
                    icon-tone="violet"
                    :title="__('Conformidade LGPD')"
                    :subtitle="__('Gestão de solicitações, métricas e auditoria da plataforma.')"
                    class="mb-4"
                />
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <a href="{{ route('admin.lgpd.metrics') }}" class="rounded-xl border border-white/80 bg-white/90 px-4 py-3 text-sm font-semibold text-violet-800 transition hover:border-violet-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/60 dark:text-violet-200 dark:hover:border-violet-600">
                        {{ __('Métricas LGPD') }}
                    </a>
                    <a href="{{ route('admin.lgpd.audit') }}" class="rounded-xl border border-white/80 bg-white/90 px-4 py-3 text-sm font-semibold text-violet-800 transition hover:border-violet-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/60 dark:text-violet-200 dark:hover:border-violet-600">
                        {{ __('Auditoria') }}
                    </a>
                    <a href="{{ route('admin.lgpd.accessibility') }}" class="rounded-xl border border-white/80 bg-white/90 px-4 py-3 text-sm font-semibold text-violet-800 transition hover:border-violet-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/60 dark:text-violet-200 dark:hover:border-violet-600">
                        {{ __('Acessibilidade') }}
                    </a>
                    <a href="{{ route('admin.lgpd.requests.index') }}" class="rounded-xl border border-white/80 bg-white/90 px-4 py-3 text-sm font-semibold text-violet-800 transition hover:border-violet-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/60 dark:text-violet-200 dark:hover:border-violet-600">
                        {{ __('Solicitações LGPD') }}
                    </a>
                </div>
            </section>
        @endif

        @if (Auth::user()?->isProfessional())
        <x-subscription-banner :banner="$subscriptionBanner" />

        <x-pending-payments-alert
            :count="$stats['pending_payments_count']"
            :total="$stats['pending_payments_total']"
        />

        <x-clinical-revenue-split-panel
            :gross="$stats['monthly_revenue']"
            :professional="$stats['monthly_professional_amount']"
            :platform-fee="$stats['monthly_platform_fee']"
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $quotaLimited = (bool) ($patientQuota['limited'] ?? false);
                $quotaAtLimit = (bool) ($patientQuota['at_limit'] ?? false);
                $quotaNearLimit = (bool) ($patientQuota['near_limit'] ?? false);
                $quotaLimit = (int) ($patientQuota['limit'] ?? 0);
                $quotaCount = (int) ($patientQuota['count'] ?? $stats['patients_count']);
                $quotaPercent = $quotaLimited && $quotaLimit > 0 ? min(100, round(($quotaCount / $quotaLimit) * 100)) : 0;
                $patientsKpiHref = $quotaAtLimit ? route('subscription.checkout') : route('patients.index');
            @endphp
            <a
                href="{{ $patientsKpiHref }}"
                class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/60 transition hover:-translate-y-0.5 hover:shadow-xl hover:ring-violet-200/80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-500 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-violet-900/30 dark:hover:ring-violet-600/40"
            >
                <div class="flex items-start justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">{{ __('Pacientes ativos') }}</p>
                        <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight text-slate-900 dark:text-white">{{ $stats['patients_count'] }}</p>
                        @if ($quotaLimited)
                            <p @class([
                                'mt-1 text-xs font-semibold',
                                'text-rose-700 dark:text-rose-300' => $quotaAtLimit,
                                'text-amber-700 dark:text-amber-300' => $quotaNearLimit && ! $quotaAtLimit,
                                'text-slate-500 dark:text-slate-400' => ! $quotaAtLimit && ! $quotaNearLimit,
                            ])>
                                {{ __(':count de :limit no plano', ['count' => $quotaCount, 'limit' => $quotaLimit]) }}
                            </p>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" role="presentation" aria-hidden="true">
                                <div @class([
                                    'h-full rounded-full transition-all',
                                    'bg-rose-500' => $quotaAtLimit,
                                    'bg-amber-500' => $quotaNearLimit && ! $quotaAtLimit,
                                    'bg-violet-500' => ! $quotaAtLimit && ! $quotaNearLimit,
                                ]) style="width: {{ $quotaPercent }}%"></div>
                            </div>
                        @endif
                    </div>
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 transition group-hover:bg-violet-100 dark:bg-violet-950/50 dark:text-violet-300 dark:group-hover:bg-violet-900/50">
                        <x-ui.icon name="users" class="h-6 w-6" />
                    </span>
                </div>
            </a>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/60 transition hover:shadow-xl hover:ring-violet-200/80 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-violet-900/30 dark:hover:ring-violet-600/40">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">{{ __('Sessões hoje') }}</p>
                        <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight text-slate-900 dark:text-white">{{ $stats['sessions_today'] }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-950/50 dark:text-sky-300">
                        <x-ui.icon name="clock" class="h-6 w-6" />
                    </span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/60 transition hover:shadow-xl hover:ring-violet-200/80 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-violet-900/30 dark:hover:ring-violet-600/40">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">{{ __('Faturamento mensal (R$)') }}</p>
                        <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight text-slate-900 dark:text-white">{{ $stats['monthly_revenue'] }}</p>
                        <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">
                            {{ __('Repasse') }}: R$ {{ $stats['monthly_professional_amount'] }}
                        </p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300">
                        <x-ui.icon name="currency" class="h-6 w-6" />
                    </span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/60 transition hover:shadow-xl hover:ring-violet-200/80 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-violet-900/30 dark:hover:ring-violet-600/40">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">{{ __('Taxa de ocupação (%)') }}</p>
                        <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight text-slate-900 dark:text-white">{{ $stats['occupancy_rate'] }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-300">
                        <x-ui.icon name="chart-bar" class="h-6 w-6" />
                    </span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <div class="space-y-6 lg:col-span-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/60 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-violet-900/30">
                    <x-ui.section-heading icon="clock" icon-tone="sky" :title="__('Sessões (14 dias)')" :subtitle="__('Volume diário de sessões agendadas ou realizadas.')" class="mb-4" />
                    <div class="mt-4 h-64" role="img" aria-labelledby="chart-sessions-trend-title" aria-describedby="chart-sessions-trend-data">
                        <span id="chart-sessions-trend-title" class="sr-only">{{ __('Sessões (14 dias)') }}</span>
                        <canvas id="chartSessionsTrend" aria-hidden="true"></canvas>
                    </div>
                    <x-chart-data-table
                        id="chart-sessions-trend-data"
                        :labels="$sessionTrend['labels']"
                        :values="$sessionTrend['values']"
                        :label-heading="__('Data')"
                        :value-heading="__('Sessões')"
                        :caption="__('Tabela de sessões por dia nos últimos 14 dias.')"
                    />
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/60 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-violet-900/30">
                    <x-ui.section-heading icon="currency" icon-tone="emerald" :title="__('Receita paga (7 dias)')" :subtitle="__('Valores liquidados por dia (R$).')" class="mb-4" />
                    <div class="mt-4 h-56" role="img" aria-labelledby="chart-revenue-trend-title" aria-describedby="chart-revenue-trend-data">
                        <span id="chart-revenue-trend-title" class="sr-only">{{ __('Receita paga (7 dias)') }}</span>
                        <canvas id="chartRevenueTrend" aria-hidden="true"></canvas>
                    </div>
                    <x-chart-data-table
                        id="chart-revenue-trend-data"
                        :labels="$revenueTrend['labels']"
                        :values="$revenueTrend['values']"
                        :label-heading="__('Data')"
                        :value-heading="__('Receita (R$)')"
                        :caption="__('Tabela de receita paga por dia nos últimos 7 dias.')"
                    />
                </div>
            </div>

            <div class="min-w-0 space-y-6 lg:col-span-2">
                <div id="notificacoes" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/60 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-violet-900/30">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <x-ui.section-heading icon="bell" icon-tone="violet" :title="__('Notificações')" :subtitle="__('Lembretes de sessões e assinatura.')" class="flex-1" />
                        @if ($notifications->whereNull('read_at')->isNotEmpty())
                            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400">
                                    {{ __('Marcar todas como lidas') }}
                                </button>
                            </form>
                        @endif
                    </div>
                    <x-notifications-feed :notifications="$notifications" />
                </div>

                <div id="agenda-hoje" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/60 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-violet-900/30">
                <div class="flex items-center justify-between gap-2">
                    <x-ui.section-heading icon="calendar" icon-tone="indigo" :title="__('Agenda de hoje')" class="flex-1" />
                    <a href="{{ route('schedule.index') }}" class="shrink-0 text-xs font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400 dark:hover:text-violet-300">{{ __('Ver agenda') }}</a>
                </div>
                <ul class="mt-4 space-y-3">
                    @forelse ($todayAgenda as $session)
                        @php
                            $badge = match ($session->status) {
                                \App\Enums\TherapySessionStatus::Completed => ['bg-emerald-50 text-emerald-800 ring-emerald-600/10', __('Concluído')],
                                \App\Enums\TherapySessionStatus::Scheduled => ['bg-sky-50 text-sky-800 ring-sky-600/10', __('Confirmado')],
                                \App\Enums\TherapySessionStatus::Cancelled => ['bg-rose-50 text-rose-800 ring-rose-600/10', __('Cancelado')],
                                default => ['bg-amber-50 text-amber-800 ring-amber-600/10', __('Pendente')],
                            };
                        @endphp
                        <li>
                            <a href="{{ route('therapy-sessions.show', $session) }}" class="block rounded-xl border border-slate-200/80 bg-gradient-to-br from-white to-violet-50/40 p-4 transition hover:border-violet-300 hover:shadow-md dark:border-slate-600 dark:from-slate-800 dark:to-violet-950/30 dark:hover:border-violet-500">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $session->patient?->name ?? __('Paciente') }}</span>
                                    <span @class(['inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ring-1 ring-inset', $badge[0]])>{{ $badge[1] }}</span>
                                </div>
                                <p class="mt-1 text-xs font-medium text-slate-600">
                                    {{ \Illuminate\Support\Carbon::parse($session->session_time)->format('H:i') }}
                                    · {{ $session->session_date?->translatedFormat('d M') }}
                                </p>
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl border border-dashed border-violet-200/60 bg-violet-50/50 px-4 py-8 text-center text-sm font-medium text-slate-600">
                            {{ __('Sem sessões agendadas para hoje.') }}
                        </li>
                    @endforelse
                </ul>
                </div>
            </div>
        </div>
        @else
            <div id="notificacoes" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/60 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-violet-900/30">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <x-ui.section-heading icon="bell" icon-tone="violet" :title="__('Notificações')" :subtitle="__('Alertas da plataforma.')" class="flex-1" />
                    @if ($notifications->whereNull('read_at')->isNotEmpty())
                        <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400">
                                {{ __('Marcar todas como lidas') }}
                            </button>
                        </form>
                    @endif
                </div>
                <x-notifications-feed :notifications="$notifications" />
            </div>
        @endif
    </div>

    @if (Auth::user()?->isProfessional())
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
        <script>
            const sessionLabels = @json($sessionTrend['labels']);
            const sessionValues = @json($sessionTrend['values']);
            const revenueLabels = @json($revenueTrend['labels']);
            const revenueValues = @json($revenueTrend['values']);
            const dark = document.documentElement.classList.contains('dark');
            const grid = dark ? 'rgba(148,163,184,0.12)' : 'rgba(15,23,42,0.06)';
            const tick = dark ? '#cbd5e1' : '#64748b';

            const lineOpts = {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { color: tick, maxRotation: 0 }, grid: { color: grid } },
                    y: { beginAtZero: true, ticks: { color: tick, precision: 0 }, grid: { color: grid } },
                },
                plugins: { legend: { display: false } },
            };

            new Chart(document.getElementById('chartSessionsTrend'), {
                type: 'line',
                data: {
                    labels: sessionLabels,
                    datasets: [{
                        data: sessionValues,
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124,58,237,0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#7c3aed',
                    }],
                },
                options: lineOpts,
            });

            new Chart(document.getElementById('chartRevenueTrend'), {
                type: 'bar',
                data: {
                    labels: revenueLabels,
                    datasets: [{
                        data: revenueValues,
                        backgroundColor: 'rgba(79,70,229,0.55)',
                        borderRadius: 8,
                        borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { ticks: { color: tick, maxRotation: 0 }, grid: { display: false } },
                        y: { beginAtZero: true, ticks: { color: tick }, grid: { color: grid } },
                    },
                    plugins: { legend: { display: false } },
                },
            });
        </script>
    @endpush
    @endif
</x-app-layout>
