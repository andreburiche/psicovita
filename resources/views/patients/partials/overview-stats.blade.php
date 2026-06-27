@props([
    'patient',
    'clinicalRecordsCount' => 0,
    'paymentsCount' => 0,
    'paymentStats' => [],
    'anamnesisFilled' => 0,
    'anamnesisTotal' => 0,
])

@php
    $sessionCount = $patient->therapySessions->count();
    $pendingTotal = (float) ($paymentStats['pending_total'] ?? 0);
@endphp

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <a
        href="{{ route('patients.show', ['patient' => $patient, 'tab' => 'overview']) }}#sessoes-recentes"
        class="group rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100 transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50 dark:hover:border-indigo-700"
    >
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Sessões') }}</p>
        <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $sessionCount }}</p>
        <p class="mt-1 text-xs text-indigo-600 opacity-0 transition group-hover:opacity-100 dark:text-indigo-400">{{ __('Ver histórico') }} →</p>
    </a>

    @can('viewAny', \App\Models\ClinicalRecord::class)
        <a
            href="{{ route('patients.show', ['patient' => $patient, 'tab' => 'clinical-records']) }}"
            class="group rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100 transition hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50 dark:hover:border-violet-700"
        >
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Prontuário') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $clinicalRecordsCount }}</p>
            <p class="mt-1 text-xs text-violet-600 opacity-0 transition group-hover:opacity-100 dark:text-violet-400">{{ __('Abrir aba') }} →</p>
        </a>
    @endcan

    @can('viewAny', \App\Models\Payment::class)
        <a
            href="{{ route('patients.show', ['patient' => $patient, 'tab' => 'payments']) }}"
            class="group rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50 dark:hover:border-emerald-700"
        >
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Pagamentos') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $paymentsCount }}</p>
            @if ($pendingTotal > 0)
                <p class="mt-1 text-xs font-semibold text-amber-600 dark:text-amber-400">R$ {{ number_format($pendingTotal, 2, ',', '.') }} {{ __('em aberto') }}</p>
            @endif
        </a>
    @endcan

    <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Anamnese') }}</p>
        @if ($anamnesisTotal > 0)
            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $anamnesisFilled }}/{{ $anamnesisTotal }}</p>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <div
                    class="h-full rounded-full bg-violet-500 transition-all"
                    style="width: {{ min(100, round(($anamnesisFilled / max(1, $anamnesisTotal)) * 100)) }}%"
                ></div>
            </div>
        @else
            <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Sem modelo') }}</p>
        @endif
    </div>
</div>
