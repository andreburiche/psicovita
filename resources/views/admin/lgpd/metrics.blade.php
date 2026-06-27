<x-app-layout>
    <x-slot name="header">{{ __('Métricas LGPD') }}</x-slot>

    <div class="mx-auto max-w-6xl space-y-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-page-hero
                :title="__('Indicadores de conformidade')"
                :subtitle="__('Visão consolidada de solicitações do titular, consentimentos de IA e retenção configurada.')"
                icon="chart-line"
                iconTone="indigo"
            />
            <div class="flex flex-wrap gap-3 text-sm font-semibold">
                <a href="{{ route('admin.lgpd.requests.index') }}" class="text-violet-600 hover:underline dark:text-violet-400">{{ __('Ver solicitações') }}</a>
                <a href="{{ route('admin.lgpd.audit') }}" class="text-violet-600 hover:underline dark:text-violet-400">{{ __('Auditoria') }}</a>
                <a href="{{ route('admin.lgpd.accessibility') }}" class="text-violet-600 hover:underline dark:text-violet-400">{{ __('Acessibilidade') }}</a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Pendentes') }}</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-amber-700 dark:text-amber-300">{{ $metrics['totals']['pending'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Em andamento') }}</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-sky-700 dark:text-sky-300">{{ $metrics['totals']['in_progress'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Concluídas (30 dias)') }}</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ $metrics['totals']['completed_last_30_days'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('SLA configurado') }}</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $metrics['sla_days'] }}<span class="text-base font-semibold text-slate-500"> {{ __('dias') }}</span></p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Resumo geral') }}</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600 dark:text-slate-400">{{ __('Total de solicitações') }}</dt>
                        <dd class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ $metrics['totals']['all'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600 dark:text-slate-400">{{ __('Concluídas (todas)') }}</dt>
                        <dd class="font-semibold tabular-nums">{{ $metrics['totals']['completed'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600 dark:text-slate-400">{{ __('Rejeitadas') }}</dt>
                        <dd class="font-semibold tabular-nums">{{ $metrics['totals']['rejected'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600 dark:text-slate-400">{{ __('Tempo médio de resolução') }}</dt>
                        <dd class="font-semibold tabular-nums">
                            @if ($metrics['avg_resolution_days'] !== null)
                                {{ $metrics['avg_resolution_days'] }} {{ __('dias') }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600 dark:text-slate-400">{{ __('Consentimentos de IA') }}</dt>
                        <dd class="font-semibold tabular-nums">{{ $metrics['totals']['ai_consents'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600 dark:text-slate-400">{{ __('CPFs com hash/criptografia') }}</dt>
                        <dd class="font-semibold tabular-nums">{{ $metrics['totals']['patients_with_cpf_encrypted'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600 dark:text-slate-400">{{ __('Retenção de solicitações') }}</dt>
                        <dd class="font-semibold tabular-nums">{{ $metrics['retention_days'] }} {{ __('dias') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                <h3 id="chart-lgpd-monthly-title" class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Solicitações por mês') }}</h3>
                <div class="mt-4 h-56" role="img" aria-labelledby="chart-lgpd-monthly-title" aria-describedby="chart-lgpd-monthly-data">
                    <canvas id="chartLgpdMonthly" aria-hidden="true"></canvas>
                </div>
                <x-chart-data-table
                    id="chart-lgpd-monthly-data"
                    :labels="$metrics['monthly']['labels']"
                    :values="$metrics['monthly']['values']"
                    :label-heading="__('Mês')"
                    :value-heading="__('Solicitações')"
                    :caption="__('Volume mensal de solicitações LGPD.')"
                />
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Por tipo de solicitação') }}</h3>
            <ul class="mt-4 divide-y divide-slate-100 dark:divide-slate-700">
                @foreach ($metrics['by_type'] as $item)
                    <li class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ $item['label'] }}</span>
                        <span class="text-sm font-bold tabular-nums text-slate-900 dark:text-white">{{ $item['count'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
        <script>
            const labels = @json($metrics['monthly']['labels']);
            const values = @json($metrics['monthly']['values']);
            const dark = document.documentElement.classList.contains('dark');
            const grid = dark ? 'rgba(148,163,184,0.12)' : 'rgba(15,23,42,0.06)';
            const tick = dark ? '#cbd5e1' : '#64748b';

            new Chart(document.getElementById('chartLgpdMonthly'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: 'rgba(124,58,237,0.55)',
                        borderRadius: 8,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { ticks: { color: tick }, grid: { display: false } },
                        y: { beginAtZero: true, ticks: { color: tick, precision: 0 }, grid: { color: grid } },
                    },
                    plugins: { legend: { display: false } },
                },
            });
        </script>
    @endpush
</x-app-layout>
