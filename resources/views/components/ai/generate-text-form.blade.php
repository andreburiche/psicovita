@props([
    'patients' => collect(),
    'returnTo' => null,
    'patientId' => null,
    'hidePatientField' => false,
    'compact' => false,
])

@php
    $approachesText = [
        'freudiana' => __('Freudiana'),
        'lacaniana' => __('Lacaniana'),
        'jungiana' => __('Jungiana'),
        'winnicottiana' => __('Winnicottiana'),
        'humanista' => __('Humanista'),
        'tcc' => __('TCC'),
        'sistemica' => __('Sistêmica'),
    ];
    $outputTypes = [
        'resumo_clinico' => __('Resumo clínico'),
        'devolutiva_paciente' => __('Devolutiva ao paciente'),
        'orientacao_pos_sessao' => __('Orientação pós-sessão'),
        'texto_acolhedor' => __('Texto acolhedor'),
        'pontos_atencao' => __('Pontos de atenção'),
    ];
@endphp

<form action="{{ route('ai.generate-text') }}" method="POST" class="space-y-4">
    @csrf
    @if ($returnTo)
        <input type="hidden" name="return_to" value="{{ $returnTo }}" />
    @endif
    @if ($hidePatientField)
        <input type="hidden" name="patient_id" value="{{ old('patient_id', $patientId) }}" data-ai-sync-patient />
    @endif

    <div>
        <label for="session_text" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Texto bruto ou resumo da sessão') }}</label>
        <textarea
            name="session_text"
            id="session_text"
            rows="{{ $compact ? 4 : 6 }}"
            required
            class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            placeholder="{{ __('Cole notas, tópicos ou transcrição parcial da sessão…') }}"
        >{{ old('session_text') }}</textarea>
        @error('session_text')
            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <x-form-select name="approach" :label="__('Abordagem')" :options="$approachesText" :value="old('approach')" :required="true" />
    <x-form-select name="output_type" :label="__('Tipo de saída')" :options="$outputTypes" :value="old('output_type')" :required="true" />

    @unless ($hidePatientField)
        @if ($patients->isNotEmpty())
            <x-form-select
                name="patient_id"
                :label="__('Paciente (opcional)')"
                :options="collect(['' => __('Não associar')])->union($patients->pluck('name', 'id'))->all()"
                :value="old('patient_id', $patientId)"
            />
        @endif
    @endunless

    <button
        type="submit"
        class="w-full rounded-xl bg-indigo-600 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 {{ $compact ? 'py-2.5' : '' }}"
    >
        {{ __('Gerar texto') }}
    </button>
</form>
