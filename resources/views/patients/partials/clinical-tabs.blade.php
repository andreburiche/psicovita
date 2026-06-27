@props([
    'patient',
    'activeTab' => 'overview',
    'clinicalRecordsCount' => 0,
    'paymentsCount' => 0,
    'documentRequestsCount' => 0,
    'sessionCount' => 0,
])

@php
    $tabClass = fn (string $tab) => [
        'inline-flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-violet-500/30 sm:px-4',
        'bg-white text-violet-700 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:text-violet-300 dark:ring-slate-600' => $activeTab === $tab,
        'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-200' => $activeTab !== $tab,
    ];

    $badgeClass = 'ml-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-slate-200/80 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-slate-600 dark:bg-slate-700 dark:text-slate-300';
    $activeBadgeClass = 'ml-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-violet-100 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-violet-700 dark:bg-violet-900/60 dark:text-violet-200';
@endphp

<nav
    class="flex gap-1 overflow-x-auto rounded-2xl border border-slate-200/90 bg-slate-100/80 p-1 shadow-inner [-ms-overflow-style:none] [scrollbar-width:none] dark:border-slate-700 dark:bg-slate-800/60 [&::-webkit-scrollbar]:hidden"
    aria-label="{{ __('Abas da ficha clínica') }}"
>
    <a
        href="{{ route('patients.show', $patient) }}"
        @if ($activeTab === 'overview') aria-current="page" @endif
        @class($tabClass('overview'))
    >
        <x-ui.icon name="dashboard" class="h-4 w-4 shrink-0 opacity-70" />
        {{ __('Resumo') }}
    </a>

    @can('viewAny', \App\Models\ClinicalRecord::class)
        <a
            href="{{ route('patients.show', ['patient' => $patient, 'tab' => 'clinical-records']) }}"
            @if ($activeTab === 'clinical-records') aria-current="page" @endif
            @class($tabClass('clinical-records'))
        >
            <x-ui.icon name="document-text" class="h-4 w-4 shrink-0 opacity-70" />
            {{ __('Prontuário') }}
            @if ($clinicalRecordsCount > 0)
                <span @class($activeTab === 'clinical-records' ? $activeBadgeClass : $badgeClass)>{{ $clinicalRecordsCount }}</span>
            @endif
        </a>
    @endcan

    @can('viewAny', \App\Models\Payment::class)
        <a
            href="{{ route('patients.show', ['patient' => $patient, 'tab' => 'payments']) }}"
            @if ($activeTab === 'payments') aria-current="page" @endif
            @class($tabClass('payments'))
        >
            <x-ui.icon name="currency" class="h-4 w-4 shrink-0 opacity-70" />
            {{ __('Financeiro') }}
            @if ($paymentsCount > 0)
                <span @class($activeTab === 'payments' ? $activeBadgeClass : $badgeClass)>{{ $paymentsCount }}</span>
            @endif
        </a>
    @endcan

    @can('viewAny', [\App\Models\PatientScaleAssessment::class, $patient])
        <a
            href="{{ route('patients.show', ['patient' => $patient, 'tab' => 'assessments']) }}"
            @if ($activeTab === 'assessments') aria-current="page" @endif
            @class($tabClass('assessments'))
        >
            <x-ui.icon name="chart-bar" class="h-4 w-4 shrink-0 opacity-70" />
            {{ __('Avaliações') }}
        </a>
    @endcan

    @can('viewAny', [\App\Models\DocumentRequest::class, $patient])
        <a
            href="{{ route('patients.show', ['patient' => $patient, 'tab' => 'document-requests']) }}"
            @if ($activeTab === 'document-requests') aria-current="page" @endif
            @class($tabClass('document-requests'))
        >
            <x-ui.icon name="document-text" class="h-4 w-4 shrink-0 opacity-70" />
            <span class="whitespace-nowrap">{{ __('Documentos') }}</span>
            @if ($documentRequestsCount > 0)
                <span @class($activeTab === 'document-requests' ? $activeBadgeClass : $badgeClass)>{{ $documentRequestsCount }}</span>
            @endif
        </a>
    @endcan
</nav>
