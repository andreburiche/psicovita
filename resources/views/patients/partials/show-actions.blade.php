@props(['patient', 'theme' => 'light'])

@php
    $isDark = $theme === 'dark';

    $secondaryBtn = $isDark
        ? 'inline-flex items-center justify-center gap-1.5 rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/15 sm:px-3.5 sm:py-2.5'
        : 'inline-flex items-center justify-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-800 shadow-sm transition hover:border-violet-300 hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950/50 dark:text-violet-200 dark:hover:bg-violet-950';

    $primaryBtn = $isDark
        ? 'inline-flex items-center justify-center gap-1.5 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-violet-900 shadow-md transition hover:bg-violet-50'
        : 'inline-flex items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500';

    $ghostBtn = $isDark
        ? 'inline-flex items-center justify-center rounded-xl border border-white/15 bg-transparent px-4 py-2.5 text-sm font-semibold text-white/90 transition hover:bg-white/10'
        : 'inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700';

    $dangerBtn = $isDark
        ? 'inline-flex items-center justify-center rounded-xl border border-red-400/30 bg-red-500/90 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500'
        : 'inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 dark:border-red-900/50';
@endphp

<div class="space-y-3">
    {{-- Atalhos clínicos --}}
    <div class="flex flex-wrap justify-center gap-2 sm:justify-start">
        @can('viewAny', [\App\Models\PatientScaleAssessment::class, $patient])
            <a href="{{ route('patients.show', ['patient' => $patient, 'tab' => 'assessments']) }}" @class($secondaryBtn)>
                <x-ui.icon name="chart-bar" class="h-4 w-4 shrink-0" />
                {{ __('Avaliações') }}
            </a>
        @endcan
        @can('create', \App\Models\ClinicalRecord::class)
            <a href="{{ route('patients.show', ['patient' => $patient, 'tab' => 'document-requests']) }}" @class($secondaryBtn)>
                <x-ui.icon name="document-text" class="h-4 w-4 shrink-0" />
                {{ __('Documentos') }}
            </a>
            <a href="{{ route('patients.conversation', $patient) }}" @class($secondaryBtn)>
                <x-ui.icon name="chat-bubble-left-right" class="h-4 w-4 shrink-0" />
                {{ __('Conversas') }}
            </a>
        @endcan
        @can('create', [\App\Models\PatientClinicalDocument::class, $patient])
            <a href="{{ route('patients.clinical-documents.create', [$patient, 'atestado']) }}" @class($secondaryBtn)>
                <x-ui.icon name="clipboard" class="h-4 w-4 shrink-0" />
                {{ __('Atestado') }}
            </a>
        @endcan
        @can('create', \App\Models\ClinicalRecord::class)
            <a href="{{ route('clinical-records.create', ['patient_id' => $patient->id]) }}" @class($secondaryBtn)>
                <x-ui.icon name="document-text" class="h-4 w-4 shrink-0" />
                {{ __('Prontuário') }}
            </a>
        @endcan
    </div>

    {{-- Ação principal + gestão --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <a href="{{ route('therapy-sessions.create', ['patient_id' => $patient->id]) }}" @class([$primaryBtn, 'w-full sm:w-auto'])>
            <x-ui.icon name="plus" class="h-4 w-4 shrink-0" />
            {{ __('Agendar sessão') }}
        </a>

        <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-end">
            <a href="{{ route('patients.edit', $patient) }}" @class([$ghostBtn, 'flex-1 sm:flex-none'])>
                {{ __('Editar') }}
            </a>
            <x-confirm-form
                method="post"
                action="{{ route('patients.destroy', $patient) }}"
                class="inline-flex flex-1 sm:flex-none"
                :title="__('Remover paciente?')"
                :message="__('Esta ação remove o utente e dados vinculados do seu painel.')"
                :hint="__('Esta operação não pode ser desfeita.')"
                :confirm-label="__('Sim, remover')"
                variant="danger"
                :validate="false"
            >
                @csrf
                @method('delete')
                <button type="submit" @class([$dangerBtn, 'w-full'])>{{ __('Excluir') }}</button>
            </x-confirm-form>
        </div>
    </div>
</div>
