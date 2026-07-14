@props(['scaleType', 'definition', 'questions', 'options', 'inputBase'])

@php
    $sectionTone = match ($scaleType) {
        \App\Enums\ClinicalScaleType::Bdi => 'indigo',
        \App\Enums\ClinicalScaleType::Stress => 'teal',
        default => 'violet',
    };

    $questionCount = count($questions);
@endphp

<div
    class="space-y-6"
    x-data="{
        total: @js($questionCount),
        answered: 0,
        countAnswered() {
            const groups = this.$root.querySelectorAll('[data-scale-question]');
            this.answered = [...groups].filter(g => g.querySelector('input[type=radio]:checked')).length;
        },
        init() {
            this.countAnswered();
        }
    }"
    @change="countAnswered()"
>
    {{-- Barra de progresso --}}
    <div class="sticky top-16 z-10 rounded-2xl border border-slate-200/90 bg-white/95 p-4 shadow-sm ring-1 ring-slate-100 backdrop-blur-md dark:border-slate-700 dark:bg-slate-900/95 dark:ring-slate-700/60">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Progresso') }}</p>
                <p class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">
                    <span x-text="answered"></span> / {{ $questionCount }} {{ __('itens respondidos') }}
                </p>
            </div>
            <div class="h-2 w-full min-w-[8rem] flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800 sm:max-w-xs">
                <div
                    class="h-full rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 transition-all duration-300"
                    :style="`width: ${total ? Math.round((answered / total) * 100) : 0}%`"
                ></div>
            </div>
        </div>
    </div>

    <x-clinical-documents.partials.section-card
        :title="__('Identificação da aplicação')"
        :description="__('Data em que a escala foi aplicada ao paciente.')"
        icon="calendar"
        tone="slate"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="assessed_at" :value="__('Data da aplicação')" class="text-slate-700 dark:text-slate-200" />
                <input type="date" id="assessed_at" name="assessed_at" required value="{{ old('assessed_at', now()->format('Y-m-d')) }}" class="{{ $inputBase }}" />
            </div>
            <div class="flex items-end">
                <p class="rounded-xl border border-slate-200/80 bg-slate-50 px-3 py-2.5 text-xs text-slate-600 dark:border-slate-600 dark:bg-slate-900/60 dark:text-slate-300">
                    {{ __('Instrução:') }} {{ __('Indique o quanto cada item afetou o paciente na última semana (incluindo hoje).') }}
                </p>
            </div>
        </div>
    </x-clinical-documents.partials.section-card>

    <x-clinical-documents.partials.section-card
        :title="__('Itens da escala')"
        :description="__('Selecione uma opção por item. As respostas são confidenciais e encriptadas.')"
        :icon="$scaleType->icon()"
        :tone="$sectionTone"
    >
        <div class="space-y-4">
            @foreach ($questions as $index => $question)
                <fieldset
                    data-scale-question
                    class="rounded-xl border border-slate-200/80 bg-slate-50/40 p-4 dark:border-slate-700 dark:bg-slate-900/30"
                >
                    <legend class="flex gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-white text-xs font-bold text-violet-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-violet-300 dark:ring-slate-600">{{ $index + 1 }}</span>
                        <span class="pt-0.5">{{ $question['text'] }}</span>
                    </legend>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($options as $value => $label)
                            @php
                                $inputId = 'q_'.$question['key'].'_'.$value;
                                $isChecked = (string) old('answers.'.$question['key'], '') === (string) $value;
                            @endphp
                            <label
                                for="{{ $inputId }}"
                                @class([
                                    'flex cursor-pointer flex-col rounded-xl border px-3 py-2.5 text-left transition',
                                    'border-violet-500 bg-violet-50 ring-2 ring-violet-500/20 dark:border-violet-500 dark:bg-violet-950/40' => $isChecked,
                                    'border-slate-200 bg-white hover:border-violet-300 dark:border-slate-600 dark:bg-slate-900 dark:hover:border-violet-700' => ! $isChecked,
                                ])
                            >
                                <span class="flex items-center gap-2">
                                    <input
                                        type="radio"
                                        id="{{ $inputId }}"
                                        name="answers[{{ $question['key'] }}]"
                                        value="{{ $value }}"
                                        @checked($isChecked)
                                        required
                                        class="text-violet-600 focus:ring-violet-500"
                                    />
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-violet-600 dark:text-violet-400">{{ $value }}</span>
                                </span>
                                <span class="mt-1 pl-6 text-xs leading-snug text-slate-600 dark:text-slate-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </div>
    </x-clinical-documents.partials.section-card>

    <x-clinical-documents.partials.section-card
        :title="__('Observações clínicas')"
        :description="__('Opcional — contexto da sessão ou ressalvas sobre a aplicação.')"
        icon="clipboard"
        tone="slate"
    >
        <x-input-label for="notes" :value="__('Notas')" class="text-slate-700 dark:text-slate-200" />
        <textarea id="notes" name="notes" rows="4" class="{{ $inputBase }} leading-relaxed" placeholder="{{ __('Ex.: paciente relatou dificuldade em item específico…') }}">{{ old('notes') }}</textarea>
    </x-clinical-documents.partials.section-card>
</div>
