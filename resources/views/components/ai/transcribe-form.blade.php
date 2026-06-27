@props([
    'patients' => collect(),
    'returnTo' => null,
    'patientId' => null,
    'hidePatientField' => false,
    'compact' => false,
])

@php
    $sessionTypes = [
        'primeira_sessao' => __('Primeira sessão'),
        'retorno' => __('Retorno'),
        'avaliacao_inicial' => __('Avaliação inicial'),
    ];
@endphp

<div class="rounded-xl border border-amber-200/90 bg-amber-50 p-3 text-xs font-medium text-amber-950 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-100">
    {{ __('Antes de enviar áudio ou dados de sessão, confirme que há consentimento do paciente para uso de ferramenta de apoio tecnológico.') }}
</div>

<form action="{{ route('ai.transcribe') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @if ($returnTo)
        <input type="hidden" name="return_to" value="{{ $returnTo }}" />
    @endif
    @if ($hidePatientField)
        <input type="hidden" name="patient_id" value="{{ old('patient_id', $patientId) }}" data-ai-sync-patient />
    @endif

    <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Arquivo de áudio') }}</label>
        <input
            type="file"
            name="audio"
            accept="audio/*,.mp3,.wav,.m4a,.webm,.ogg"
            required
            class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-slate-300 dark:file:bg-indigo-950 dark:file:text-indigo-200"
        />
        @error('audio')
            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    @unless ($hidePatientField)
        <div>
            <label for="patient_name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Nome do paciente (opcional)') }}</label>
            <input
                type="text"
                name="patient_name"
                id="patient_name"
                value="{{ old('patient_name') }}"
                class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                maxlength="200"
            />
        </div>

        @if ($patients->isNotEmpty())
            <x-form-select
                name="patient_id"
                :label="__('Associar a paciente (opcional)')"
                :options="collect(['' => __('Não associar')])->union($patients->pluck('name', 'id'))->all()"
                :value="old('patient_id', $patientId)"
            />
        @endif
    @endunless

    <x-form-select name="session_type" :label="__('Tipo de sessão')" :options="$sessionTypes" :value="old('session_type')" :required="true" />

    <label class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-300">
        <input
            type="checkbox"
            name="lgpd_audio_consent"
            value="1"
            class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
            {{ old('lgpd_audio_consent') ? 'checked' : '' }}
            required
        />
        <span>{{ __('Confirmo que existe consentimento informado para processar este áudio nesta ferramenta de apoio.') }}</span>
    </label>
    @error('lgpd_audio_consent')
        <p class="text-sm text-rose-600">{{ $message }}</p>
    @enderror

    <button
        type="submit"
        class="w-full rounded-xl bg-indigo-600 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 {{ $compact ? 'py-2.5' : '' }}"
    >
        {{ __('Transcrever áudio') }}
    </button>
</form>
