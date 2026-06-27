<x-app-layout>
    <x-slot name="header">{{ $patient->name }}</x-slot>

    @php
        $activeTab = $activeTab ?? 'overview';
        $anamnesisFilled = 0;
        $anamnesisTotal = 0;

        if ($selectedForm && $selectedForm->questions->isNotEmpty()) {
            $anamnesisTotal = $selectedForm->questions->count();
            $anamnesisFilled = $selectedForm->questions->filter(
                fn ($q) => filled($anamnesisAnswers[$q->id] ?? null)
            )->count();
        }

        $documentRequestsCount = isset($documentRequests) ? $documentRequests->count() : 0;

        $tabLabels = [
            'overview' => __('Resumo'),
            'clinical-records' => __('Prontuário'),
            'payments' => __('Financeiro'),
            'document-requests' => __('Documentos'),
            'assessments' => __('Avaliações'),
        ];
        $breadcrumbItems = [
            ['label' => __('Pacientes'), 'href' => route('patients.index')],
            ['label' => $patient->name, 'href' => route('patients.show', $patient)],
        ];
        if ($activeTab !== 'overview' && isset($tabLabels[$activeTab])) {
            $breadcrumbItems[] = ['label' => $tabLabels[$activeTab]];
        }
    @endphp

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-patient-breadcrumb :items="$breadcrumbItems" />

            <x-patient-profile-header :patient="$patient">
                <x-slot name="actions">
                    @include('patients.partials.portal-status', ['patient' => $patient, 'portalContext' => $portalContext])

                    <div class="mt-5 border-t border-white/10 pt-5">
                        <p class="mb-3 text-center text-[11px] font-bold uppercase tracking-wider text-violet-200/70 sm:text-left">
                            {{ __('Ações rápidas') }}
                        </p>
                        @include('patients.partials.show-actions', ['patient' => $patient, 'theme' => 'dark'])
                    </div>
                </x-slot>
            </x-patient-profile-header>

            <div class="sticky top-0 z-20 -mx-4 border-b border-slate-200/80 bg-slate-50/95 px-4 py-3 backdrop-blur-md dark:border-slate-700/80 dark:bg-slate-950/90 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                @include('patients.partials.clinical-tabs', [
                    'patient' => $patient,
                    'activeTab' => $activeTab,
                    'clinicalRecordsCount' => $clinicalRecordsCount ?? 0,
                    'paymentsCount' => $paymentsCount ?? 0,
                    'documentRequestsCount' => $documentRequestsCount,
                    'sessionCount' => $patient->therapySessions->count(),
                ])
            </div>

            @if ($activeTab !== 'overview')
                @include('patients.partials.tab-context-header', ['activeTab' => $activeTab])
            @endif

            @if ($activeTab === 'overview')
                @include('patients.partials.overview-stats', [
                    'patient' => $patient,
                    'clinicalRecordsCount' => $clinicalRecordsCount ?? 0,
                    'paymentsCount' => $paymentsCount ?? 0,
                    'paymentStats' => $paymentStats ?? [],
                    'anamnesisFilled' => $anamnesisFilled,
                    'anamnesisTotal' => $anamnesisTotal,
                ])

                @include('patients.partials.clinical-documents-generate', ['patient' => $patient])

                @include('patients.partials.register-notes', ['patient' => $patient])

                @php
                    $anamnesisCompact = $anamnesisForms->isEmpty()
                        || ! $selectedForm
                        || $selectedForm->questions->isEmpty();
                @endphp

                <div @class([
                    'grid gap-6',
                    'lg:grid-cols-2 lg:items-start' => $anamnesisCompact,
                ])>
                    @include('patients.partials.anamnesis-section', [
                        'patient' => $patient,
                        'anamnesisForms' => $anamnesisForms,
                        'selectedForm' => $selectedForm,
                        'anamnesisAnswers' => $anamnesisAnswers,
                    ])

                    @include('patients.partials.recent-sessions-section', ['patient' => $patient])
                </div>

                @can('viewAny', \App\Models\ClinicalRecord::class)
                    @include('patients.partials.clinical-records-summary', [
                        'patient' => $patient,
                        'clinicalRecordsCount' => $clinicalRecordsCount ?? 0,
                        'latestClinicalRecord' => $latestClinicalRecord ?? null,
                    ])
                @endcan

                @can('viewAny', \App\Models\Payment::class)
                    @include('patients.partials.payments-summary', [
                        'patient' => $patient,
                        'paymentsCount' => $paymentsCount ?? 0,
                        'latestPayment' => $latestPayment ?? null,
                        'paymentStats' => $paymentStats ?? [],
                    ])
                @endcan
            @elseif ($activeTab === 'assessments')
                @can('viewAny', [\App\Models\PatientScaleAssessment::class, $patient])
                    @include('patients.partials.assessments-section', [
                        'patient' => $patient,
                        'scaleAssessmentHistory' => $scaleAssessmentHistory ?? collect(),
                        'scaleChartData' => $scaleChartData ?? [],
                        'scaleLatest' => $scaleLatest ?? [],
                        'therapeuticGoals' => $therapeuticGoals ?? collect(),
                    ])
                @else
                    @include('patients.partials.tab-unavailable', [
                        'title' => __('Avaliações indisponíveis'),
                        'message' => __('Não tem permissão para ver avaliações deste paciente.'),
                        'href' => route('patients.show', $patient),
                        'linkLabel' => __('Voltar ao resumo'),
                    ])
                @endcan
            @elseif ($activeTab === 'document-requests')
                @include('patients.partials.document-requests-section', [
                    'patient' => $patient,
                    'documentRequests' => $documentRequests ?? collect(),
                    'patientDocuments' => $patientDocuments ?? collect(),
                    'clinicalDocuments' => $clinicalDocuments ?? collect(),
                ])
            @elseif ($activeTab === 'clinical-records')
                @if (isset($clinicalRecords))
                    @include('patients.partials.clinical-records-section', [
                        'patient' => $patient,
                        'clinicalRecords' => $clinicalRecords,
                    ])
                @else
                    @include('patients.partials.tab-unavailable', [
                        'title' => __('Prontuário indisponível'),
                        'message' => __('Não tem permissão para ver o prontuário deste paciente ou o conteúdo não pôde ser carregado.'),
                        'href' => route('patients.show', $patient),
                        'linkLabel' => __('Voltar ao resumo'),
                    ])
                @endif
            @elseif ($activeTab === 'payments')
                @if (isset($payments))
                    @include('patients.partials.payments-section', [
                        'patient' => $patient,
                        'payments' => $payments,
                        'paymentStats' => $paymentStats,
                    ])
                @else
                    @include('patients.partials.tab-unavailable', [
                        'title' => __('Financeiro indisponível'),
                        'message' => __('Não tem permissão para ver pagamentos deste paciente ou o conteúdo não pôde ser carregado.'),
                        'href' => route('patients.show', $patient),
                        'linkLabel' => __('Voltar ao resumo'),
                    ])
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
