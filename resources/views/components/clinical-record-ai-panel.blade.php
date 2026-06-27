@props([
    'patients',
    'patientId' => null,
    'defaultOpen' => false,
])

@php
    $hasAiErrors = $errors->hasAny(['audio', 'session_text', 'lgpd_audio_consent', 'approach', 'output_type', 'session_type']);
    $shouldOpen = $defaultOpen || $hasAiErrors || session()->has('ai_content');
@endphp

<section
    id="apoio-ia"
    class="scroll-mt-24 overflow-hidden rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-white via-indigo-50/30 to-violet-50/40 shadow-sm ring-1 ring-indigo-100/70 dark:border-indigo-900/50 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/30 dark:ring-indigo-900/40"
    x-data="{ open: @json($shouldOpen) }"
>
    <button
        type="button"
        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20"
        @click="open = ! open"
        :aria-expanded="open"
        aria-controls="clinical-record-ai-panel-body"
    >
        <div class="flex min-w-0 items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/25" aria-hidden="true">
                <x-ui.icon name="sparkles" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Apoio da IA (opcional)') }}</p>
                <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ __('Transcreva áudio ou gere texto clínico. O resultado preenche o campo abaixo para revisão.') }}</p>
            </div>
        </div>
        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200/80 dark:bg-slate-800/80 dark:text-indigo-300 dark:ring-indigo-800">
            <span x-text="open ? '{{ __('Ocultar') }}' : '{{ __('Usar IA') }}'"></span>
            <x-ui.icon name="chevron-down" class="h-4 w-4 transition" x-bind:class="open ? 'rotate-180' : ''" />
        </span>
    </button>

    <div
        id="clinical-record-ai-panel-body"
        x-show="open"
        x-cloak
        class="border-t border-indigo-100/80 dark:border-indigo-900/50"
    >
        <div class="space-y-4 p-5">
            <p class="rounded-xl border border-slate-200/90 bg-white/80 px-4 py-3 text-xs leading-relaxed text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
                {{ __('A IA não substitui a sua decisão clínica. Revise sempre o conteúdo antes de salvar no prontuário.') }}
                <a href="{{ route('ai.index') }}" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Abrir assistente completo') }}</a>
            </p>

            <div class="grid gap-5 lg:grid-cols-2">
                <x-ai-card :title="__('Transcrever sessão')" :subtitle="__('Envio de áudio com consentimento explícito.')" class="!p-5">
                    <x-ai.transcribe-form
                        :patients="$patients"
                        return-to="clinical-records.create"
                        :patient-id="$patientId"
                        hide-patient-field
                        compact
                    />
                </x-ai-card>

                <x-ai-card :title="__('Gerar texto por abordagem')" :subtitle="__('Respostas éticas, sem diagnóstico fechado.')" class="!p-5">
                    <x-ai.generate-text-form
                        :patients="$patients"
                        return-to="clinical-records.create"
                        :patient-id="$patientId"
                        hide-patient-field
                        compact
                    />
                </x-ai-card>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const patientSelect = document.getElementById('patient_id');
        if (! patientSelect) {
            return;
        }

        const syncPatient = () => {
            document.querySelectorAll('[data-ai-sync-patient]').forEach((input) => {
                input.value = patientSelect.value;
            });
        };

        patientSelect.addEventListener('change', syncPatient);
        syncPatient();
    });
</script>
