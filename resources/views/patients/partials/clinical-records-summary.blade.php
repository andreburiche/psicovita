@props([
    'patient',
    'clinicalRecordsCount' => 0,
    'latestClinicalRecord' => null,
])

<section
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60"
    aria-label="{{ __('Resumo do prontuário') }}"
>
    <div class="flex flex-col gap-4 border-b border-slate-100 bg-gradient-to-r from-violet-50/80 to-indigo-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:from-violet-950/40 dark:to-indigo-950/30">
        <div class="min-w-0">
            <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-violet-900 dark:text-violet-200">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-600/10 text-violet-600 dark:bg-violet-400/15 dark:text-violet-300" aria-hidden="true">
                    <x-ui.icon name="document-text" class="h-4 w-4" />
                </span>
                {{ __('Prontuário') }}
            </h3>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                @if ($clinicalRecordsCount > 0)
                    {{ trans_choice(':count registro clínico|:count registros clínicos', $clinicalRecordsCount, ['count' => $clinicalRecordsCount]) }}
                    @if ($latestClinicalRecord)
                        · {{ __('Último em :date', ['date' => $latestClinicalRecord->created_at->format('d/m/Y')]) }}
                    @endif
                @else
                    {{ __('Ainda sem registros clínicos para este paciente.') }}
                @endif
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a
                href="{{ route('clinical-records.create', ['patient_id' => $patient->id]) }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-500"
            >
                <x-ui.icon name="plus" class="h-3.5 w-3.5" />
                {{ __('Novo registro') }}
            </a>
            <a
                href="{{ route('patients.show', ['patient' => $patient, 'tab' => 'clinical-records']) }}"
                class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-violet-700 transition hover:border-violet-200 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:text-violet-300 dark:hover:bg-slate-700"
            >{{ __('Ver histórico') }} →</a>
        </div>
    </div>

    @if ($latestClinicalRecord)
        <div class="p-5">
            <a
                href="{{ route('clinical-records.show', $latestClinicalRecord) }}"
                class="group block rounded-xl border border-violet-200/70 bg-violet-50/40 p-4 transition hover:border-violet-300 hover:bg-violet-50 dark:border-violet-800/50 dark:bg-violet-950/25 dark:hover:border-violet-700"
            >
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">{{ __('Último registro') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                    {{ $latestClinicalRecord->created_at->format('d/m/Y H:i') }}
                </p>
                <p class="mt-2 line-clamp-2 text-sm text-slate-600 dark:text-slate-300">
                    {{ \Illuminate\Support\Str::of((string) $latestClinicalRecord->content)->squish()->limit(140) }}
                </p>
                <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-violet-600 dark:text-violet-400">
                    {{ __('Abrir registro') }}
                    <x-ui.icon name="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" />
                </span>
            </a>
        </div>
    @endif
</section>
