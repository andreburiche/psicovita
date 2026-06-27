<x-app-layout>
    <x-slot name="header">{{ $title }}</x-slot>

    <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
        <x-page-hero :title="$title" :subtitle="__('Indicadores agregados do seu consultório.')" icon="chart-bar" />

        <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-violet-50/25 to-indigo-50/25 p-6 shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/70 dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-violet-950/20 dark:shadow-black/20 dark:ring-violet-900/30">
            <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-violet-500/12 blur-3xl dark:bg-violet-500/10" aria-hidden="true"></div>
            <p class="relative text-sm text-slate-600 dark:text-slate-300">
                {{ __('Pacientes com sessão nos últimos 90 dias:') }}
                <span class="font-bold tabular-nums text-slate-900 dark:text-white">{{ $charts['active_patients'] }}</span>
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                <x-ui.section-heading icon="clock" icon-tone="sky" :title="__('Sessões por mês')" class="mb-4" />
                <div class="mt-4 h-64" role="img" aria-labelledby="chart-sessions-month-title" aria-describedby="chart-sessions-month-data">
                    <span id="chart-sessions-month-title" class="sr-only">{{ __('Sessões por mês') }}</span>
                    <canvas id="chartSessions" aria-hidden="true"></canvas>
                </div>
                <x-chart-data-table
                    id="chart-sessions-month-data"
                    :labels="$charts['labels']"
                    :values="$charts['sessions_per_month']"
                    :label-heading="__('Mês')"
                    :value-heading="__('Sessões')"
                    :caption="__('Tabela de sessões realizadas por mês.')"
                />
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                <x-ui.section-heading icon="currency" icon-tone="emerald" :title="__('Receita paga (R$) por mês')" class="mb-4" />
                <div class="mt-4 h-64" role="img" aria-labelledby="chart-revenue-month-title" aria-describedby="chart-revenue-month-data">
                    <span id="chart-revenue-month-title" class="sr-only">{{ __('Receita paga (R$) por mês') }}</span>
                    <canvas id="chartRevenue" aria-hidden="true"></canvas>
                </div>
                <x-chart-data-table
                    id="chart-revenue-month-data"
                    :labels="$charts['labels']"
                    :values="$charts['revenue_per_month']"
                    :label-heading="__('Mês')"
                    :value-heading="__('Receita (R$)')"
                    :caption="__('Tabela de receita paga por mês.')"
                />
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
        <script>
            const labels = @json($charts['labels']);
            const sessionsData = @json($charts['sessions_per_month']);
            const revenueData = @json($charts['revenue_per_month']);
            const gridColor = 'rgba(15,23,42,0.06)';
            const tickColor = '#64748b';

            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { color: tickColor }, grid: { color: gridColor } },
                    y: { beginAtZero: true, ticks: { color: tickColor }, grid: { color: gridColor } },
                },
                plugins: { legend: { display: false } },
            };

            new Chart(document.getElementById('chartSessions'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: '{{ __('Sessões') }}',
                        data: sessionsData,
                        backgroundColor: 'rgba(124, 58, 237, 0.55)',
                        borderRadius: 8,
                    }],
                },
                options: commonOptions,
            });

            new Chart(document.getElementById('chartRevenue'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'R$',
                        data: revenueData,
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        fill: true,
                        tension: 0.35,
                    }],
                },
                options: commonOptions,
            });
        </script>
    @endpush
</x-app-layout>
