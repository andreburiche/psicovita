@props(['patient', 'documentType', 'defaultBody', 'inputBase'])

@php
    $sectionTone = match ($documentType) {
        \App\Enums\PatientClinicalDocumentType::Declaracao => 'indigo',
        \App\Enums\PatientClinicalDocumentType::Receita => 'teal',
        default => 'violet',
    };
@endphp

<div class="space-y-6">
    <x-clinical-documents.partials.section-card
        :title="__('Identificação do documento')"
        :description="__('Data e local que aparecem no cabeçalho do PDF.')"
        icon="calendar"
        tone="slate"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="issued_at" :value="__('Data do documento')" class="text-slate-700 dark:text-slate-200" />
                <input type="date" id="issued_at" name="issued_at" required value="{{ old('issued_at', now()->format('Y-m-d')) }}" class="{{ $inputBase }}" />
            </div>
            <div>
                <x-input-label for="place" :value="__('Local (cidade)')" class="text-slate-700 dark:text-slate-200" />
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-violet-400 dark:text-violet-500" aria-hidden="true">
                        <x-ui.icon name="map-pin" class="h-4 w-4" />
                    </span>
                    <input type="text" id="place" name="place" value="{{ old('place') }}" placeholder="{{ __('Ex.: São Paulo') }}" class="{{ $inputBase }} pl-10" />
                </div>
            </div>
        </div>
    </x-clinical-documents.partials.section-card>

    @if ($documentType === \App\Enums\PatientClinicalDocumentType::Atestado)
        <x-clinical-documents.partials.section-card
            :title="__('Tipo e período')"
            :description="__('Define se o atestado comprova comparecimento ou afastamento.')"
            icon="clipboard"
            :tone="$sectionTone"
        >
            <fieldset class="grid gap-3 sm:grid-cols-2">
                <legend class="sr-only">{{ __('Tipo de atestado') }}</legend>
                @foreach ([
                    'comparecimento' => ['label' => __('Comparecimento'), 'hint' => __('Consulta em data específica')],
                    'afastamento' => ['label' => __('Afastamento'), 'hint' => __('Ausência por período')],
                ] as $value => $meta)
                    <label
                        class="flex cursor-pointer flex-col rounded-xl border border-slate-200 p-4 transition hover:border-violet-300 dark:border-slate-600 dark:hover:border-violet-700"
                        :class="kind === @js($value) ? 'border-violet-500 bg-violet-50 ring-2 ring-violet-500/20 dark:border-violet-500 dark:bg-violet-950/40' : ''"
                    >
                        <span class="flex items-center gap-2">
                            <input type="radio" name="atestado_kind" value="{{ $value }}" x-model="kind" class="text-violet-600 focus:ring-violet-500" />
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $meta['label'] }}</span>
                        </span>
                        <span class="mt-1 pl-6 text-xs text-slate-500 dark:text-slate-400">{{ $meta['hint'] }}</span>
                    </label>
                @endforeach
            </fieldset>

            <div class="mt-5" x-show="kind === 'comparecimento'" x-cloak>
                <x-input-label for="session_date" :value="__('Data da consulta')" class="text-slate-700 dark:text-slate-200" />
                <input type="date" id="session_date" name="session_date" value="{{ old('session_date', now()->format('Y-m-d')) }}" class="{{ $inputBase }}" />
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-3" x-show="kind === 'afastamento'" x-cloak>
                <div>
                    <x-input-label for="days" :value="__('Dias')" class="text-slate-700 dark:text-slate-200" />
                    <input type="number" min="1" max="365" id="days" name="days" value="{{ old('days', 1) }}" class="{{ $inputBase }}" @input="syncEndFromDays()" />
                </div>
                <div>
                    <x-input-label for="start_date" :value="__('Início')" class="text-slate-700 dark:text-slate-200" />
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}" class="{{ $inputBase }}" @input="syncEndFromDays()" />
                </div>
                <div>
                    <x-input-label for="end_date" :value="__('Fim')" class="text-slate-700 dark:text-slate-200" />
                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="{{ old('end_date', now()->format('Y-m-d')) }}"
                        class="{{ $inputBase }}"
                        :class="dateError ? 'border-rose-400 ring-2 ring-rose-500/20 focus:border-rose-500 focus:ring-rose-500/20' : ''"
                        @input="syncDaysFromRange()"
                    />
                </div>
            </div>

            <p x-show="kind === 'afastamento' && dateError" x-cloak class="mt-3 flex items-start gap-2 text-xs text-rose-600 dark:text-rose-400" role="alert">
                <x-ui.icon name="alert-triangle" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                <span x-text="dateError"></span>
            </p>

            <div class="mt-5">
                <x-input-label for="cid" :value="__('CID (opcional)')" class="text-slate-700 dark:text-slate-200" />
                <input type="text" id="cid" name="cid" value="{{ old('cid') }}" class="{{ $inputBase }}" placeholder="Ex.: F41.1" />
                <p class="mt-1.5 text-xs text-slate-500">{{ __('Inclua somente quando necessário e autorizado pelo paciente.') }}</p>
            </div>
        </x-clinical-documents.partials.section-card>
    @endif

    @if ($documentType === \App\Enums\PatientClinicalDocumentType::Declaracao)
        <x-clinical-documents.partials.section-card
            :title="__('Assunto')"
            :description="__('Opcional — aparece como título no PDF.')"
            icon="clipboard"
            :tone="$sectionTone"
        >
            <x-input-label for="subject" :value="__('Título / assunto')" class="text-slate-700 dark:text-slate-200" />
            <input type="text" id="subject" name="subject" value="{{ old('subject') }}" placeholder="{{ __('Ex.: Declaração de acompanhamento psicológico') }}" class="{{ $inputBase }}" />
        </x-clinical-documents.partials.section-card>
    @endif

    @if ($documentType === \App\Enums\PatientClinicalDocumentType::Receita)
        <x-clinical-documents.partials.section-card
            :title="__('Prescrição')"
            :description="__('Medicamentos, doses e orientações de uso.')"
            icon="stethoscope"
            :tone="$sectionTone"
        >
            <x-input-label for="medications" :value="__('Medicamentos / posologia')" class="text-slate-700 dark:text-slate-200" />
            <textarea
                id="medications"
                name="medications"
                rows="8"
                required
                class="{{ $inputBase }} font-mono text-[13px] leading-relaxed"
                placeholder="{{ __('Um item por linha. Ex.:') }}&#10;Fluoxetina 20 mg — 1 comprimido pela manhã&#10;Melatonina 3 mg — 1 comprimido à noite"
            >{{ old('medications') }}</textarea>

            <div class="mt-4">
                <x-input-label for="observations" :value="__('Observações')" class="text-slate-700 dark:text-slate-200" />
                <textarea id="observations" name="observations" rows="3" class="{{ $inputBase }}">{{ old('observations') }}</textarea>
            </div>

            <p class="mt-4 flex gap-2 rounded-xl border border-amber-200/80 bg-amber-50/70 px-3 py-2.5 text-xs text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
                <x-ui.icon name="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0" />
                {{ __('A prescrição é de responsabilidade do profissional habilitado. Verifique a legislação do seu conselho.') }}
            </p>
        </x-clinical-documents.partials.section-card>
        <input type="hidden" name="body" value="{{ old('body', __('Receituário emitido conforme prescrição abaixo.')) }}" />
    @else
        <x-clinical-documents.partials.section-card
            :title="__('Texto do documento')"
            :description="__('Conteúdo principal impresso no PDF. Edite livremente ou use o modelo sugerido.')"
            icon="document-text"
            :tone="$sectionTone"
        >
            @if ($documentType === \App\Enums\PatientClinicalDocumentType::Atestado)
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Preencha os campos acima e aplique o modelo, ou escreva manualmente.') }}</p>
                    <button
                        type="button"
                        @click="applyTemplate()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 transition hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950/50 dark:text-violet-300"
                    >
                        <x-ui.icon name="sparkles" class="h-3.5 w-3.5" />
                        {{ __('Aplicar modelo') }}
                    </button>
                </div>
            @endif
            <x-input-label for="body" :value="__('Conteúdo')" class="text-slate-700 dark:text-slate-200" />
            <textarea id="body" name="body" rows="10" required class="{{ $inputBase }} leading-relaxed">{{ old('body', $defaultBody) }}</textarea>
        </x-clinical-documents.partials.section-card>
    @endif
</div>
