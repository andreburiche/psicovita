@props([
    'patient',
    'scaleAssessmentHistory' => collect(),
    'scaleChartData' => [],
    'scaleLatest' => [],
    'therapeuticGoals' => collect(),
])

@php
    use App\Enums\ClinicalScaleType;
    use App\Enums\TherapeuticGoalStatus;
    use App\Support\ClinicalScaleCatalog;

    $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900';
@endphp

<div class="space-y-6" data-test="patient-assessments-tab">
    {{-- Indicadores de risco --}}
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach (ClinicalScaleType::cases() as $scale)
            @php
                $latest = $scaleLatest[$scale->value] ?? null;
                $tone = $latest ? ClinicalScaleCatalog::severityTone($latest->severity) : 'slate';
            @endphp
            <div @class([
                'rounded-2xl border p-4 shadow-sm ring-1',
                'border-emerald-200/80 bg-emerald-50/50 ring-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/20' => $tone === 'emerald',
                'border-sky-200/80 bg-sky-50/50 ring-sky-100 dark:border-sky-900/40 dark:bg-sky-950/20' => $tone === 'sky',
                'border-amber-200/80 bg-amber-50/50 ring-amber-100 dark:border-amber-900/40 dark:bg-amber-950/20' => $tone === 'amber',
                'border-rose-200/80 bg-rose-50/50 ring-rose-100 dark:border-rose-900/40 dark:bg-rose-950/20' => $tone === 'rose',
                'border-slate-200/80 bg-white ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80' => $tone === 'slate',
            ])>
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $scale->label() }}</p>
                        @if ($latest)
                            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $latest->total_score }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $latest->severity_label }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">{{ $latest->assessed_at->format('d/m/Y') }}</p>
                        @else
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('Sem aplicações') }}</p>
                        @endif
                    </div>
                    @if ($latest?->is_risk)
                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-1 text-[10px] font-bold uppercase text-rose-800 dark:bg-rose-950 dark:text-rose-200">
                            <x-ui.icon name="alert-triangle" class="h-3 w-3" />
                            {{ __('Atenção') }}
                        </span>
                    @endif
                </div>
                @can('create', [\App\Models\PatientScaleAssessment::class, $patient])
                    <a href="{{ route('patients.scale-assessments.create', [$patient, $scale->value]) }}" class="mt-3 inline-flex text-xs font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400">
                        {{ __('Aplicar escala') }} →
                    </a>
                @endcan
            </div>
        @endforeach
    </div>

    {{-- Gráfico de evolução --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
        <x-ui.section-heading icon="chart-bar" icon-tone="violet" :title="__('Evolução dos resultados')" :subtitle="__('Comparação temporal das pontuações por escala.')" class="mb-4" />
        <div class="h-72">
            <canvas id="scaleEvolutionChart" aria-hidden="true"></canvas>
        </div>
    </div>

    {{-- Objetivos terapêuticos --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-700">
            <x-ui.section-heading icon="check-circle" icon-tone="teal" :title="__('Objetivos terapêuticos')" :subtitle="__('Acompanhe metas acordadas com o paciente.')" />
        </div>
        <div class="space-y-4 p-5">
            @can('manageGoals', [\App\Models\PatientScaleAssessment::class, $patient])
                <form method="post" action="{{ route('patients.therapeutic-goals.store', $patient) }}" class="rounded-xl border border-dashed border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="goal_title" :value="__('Novo objetivo')" />
                            <input id="goal_title" name="title" type="text" required class="{{ $inputBase }}" placeholder="{{ __('Ex.: Reduzir episódios de ansiedade na semana') }}" />
                        </div>
                        <div>
                            <x-input-label for="goal_status" :value="__('Estado')" />
                            <select id="goal_status" name="status" class="{{ $inputBase }}">
                                @foreach (TherapeuticGoalStatus::options() as $value => $label)
                                    <option value="{{ $value }}" @selected($value === TherapeuticGoalStatus::InProgress->value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="goal_progress" :value="__('Progresso (%)')" />
                            <input id="goal_progress" name="progress_percent" type="number" min="0" max="100" value="0" class="{{ $inputBase }}" />
                        </div>
                        <div>
                            <x-input-label for="goal_target" :value="__('Meta (data)')" />
                            <input id="goal_target" name="target_date" type="date" class="{{ $inputBase }}" />
                        </div>
                        <div class="flex items-end sm:col-span-1">
                            <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-500">{{ __('Adicionar') }}</button>
                        </div>
                    </div>
                </form>
            @endcan

            @forelse ($therapeuticGoals as $goal)
                <div class="rounded-xl border border-slate-200/80 p-4 dark:border-slate-700">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $goal->title }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $goal->status->label() }}
                                @if ($goal->target_date) · {{ __('Meta') }} {{ $goal->target_date->format('d/m/Y') }} @endif
                            </p>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-teal-500 transition-all" style="width: {{ $goal->progress_percent }}%"></div>
                            </div>
                        </div>
                        @can('deleteGoal', $goal)
                            <x-confirm-form
                                method="post"
                                :action="route('patients.therapeutic-goals.destroy', [$patient, $goal])"
                                :title="__('Remover objetivo?')"
                                :message="__('Este objetivo será excluído permanentemente.')"
                                :confirm-label="__('Sim, remover')"
                                variant="danger"
                                :validate="false"
                                class="inline"
                            >
                                @csrf
                                @method('delete')
                                <button type="submit" class="text-xs font-semibold text-rose-600">{{ __('Remover') }}</button>
                            </x-confirm-form>
                        @endcan
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Nenhum objetivo registado ainda.') }}</p>
            @endforelse
        </div>
    </div>

    {{-- Histórico --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-700">
            <x-ui.section-heading icon="clipboard-list" icon-tone="indigo" :title="__('Histórico de avaliações')" />
            <div class="flex flex-wrap gap-2">
                @foreach (ClinicalScaleType::cases() as $scale)
                    <a href="{{ route('patients.scale-assessments.create', [$patient, $scale->value]) }}" class="inline-flex items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950/50 dark:text-violet-300">
                        <x-ui.icon :name="$scale->icon()" class="h-3.5 w-3.5" />
                        {{ $scale->label() }}
                    </a>
                @endforeach
            </div>
        </div>

        @if ($scaleAssessmentHistory->isEmpty())
            <p class="p-6 text-sm text-slate-500 dark:text-slate-400">{{ __('Aplique uma escala para iniciar o acompanhamento longitudinal.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-900/60">
                        <tr>
                            <th class="px-5 py-3">{{ __('Data') }}</th>
                            <th class="px-5 py-3">{{ __('Escala') }}</th>
                            <th class="px-5 py-3">{{ __('Pontuação') }}</th>
                            <th class="px-5 py-3">{{ __('Classificação') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($scaleAssessmentHistory as $assessment)
                            <tr>
                                <td class="px-5 py-3 tabular-nums">{{ $assessment->assessed_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">{{ $assessment->scale_type->label() }}</td>
                                <td class="px-5 py-3 font-bold tabular-nums">{{ $assessment->total_score }}</td>
                                <td class="px-5 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200' => $assessment->is_risk,
                                        'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' => ! $assessment->is_risk,
                                    ])>{{ $assessment->severity_label }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @can('delete', $assessment)
                                        <x-confirm-form
                                            method="post"
                                            :action="route('patients.scale-assessments.destroy', [$patient, $assessment])"
                                            :title="__('Remover avaliação?')"
                                            :message="__('Esta aplicação da escala será excluída do histórico.')"
                                            :confirm-label="__('Sim, remover')"
                                            variant="danger"
                                            :validate="false"
                                            class="inline"
                                        >
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="text-xs font-semibold text-rose-600">{{ __('Remover') }}</button>
                                        </x-confirm-form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    @php
        $scaleTypesJson = collect(ClinicalScaleType::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => $s->label(),
            'color' => $s->chartColor(),
        ])->values();
    @endphp
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
    <script>
        const scaleChartData = @json($scaleChartData);
        const scaleTypes = @json($scaleTypesJson);

        const datasets = scaleTypes.map(s => {
            const data = scaleChartData[s.value];
            if (!data?.labels?.length) return null;
            return {
                label: s.label,
                data: data.labels.map((label, i) => ({ x: label, y: data.scores[i] })),
                borderColor: s.color,
                backgroundColor: s.color.replace('0.85', '0.15'),
                tension: 0.3,
            };
        }).filter(Boolean);

        if (datasets.length && document.getElementById('scaleEvolutionChart')) {
            new Chart(document.getElementById('scaleEvolutionChart'), {
                type: 'line',
                data: { datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    parsing: { xAxisKey: 'x', yAxisKey: 'y' },
                    scales: {
                        x: { type: 'category', title: { display: true, text: @js(__('Data')) } },
                        y: { beginAtZero: true, title: { display: true, text: @js(__('Pontuação')) } },
                    },
                },
            });
        }
    </script>
@endpush
