<x-app-layout>
    <x-slot name="header">{{ __('Novo registro clínico') }}</x-slot>

    @php
        $formId = 'clinical-record-store';
        $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
        $selectedPatientId = old('patient_id', request('patient_id', session('ai_patient_id')));
        $aiHeader = '— '.__('Nota de apoio (IA) — revisão profissional obrigatória')." —\n\n";
        $contentValue = old('content');
        if ($contentValue === null && session('ai_content')) {
            $contentValue = $aiHeader.session('ai_content');
        }
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-page-hero :title="__('Novo registro clínico')" :subtitle="__('Registre informações sensíveis no prontuário (conteúdo encriptado em repouso).')" icon="document">
                <x-slot name="actions">
                    <a
                        href="{{ route('clinical-records.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ __('Voltar') }}
                    </a>
                </x-slot>
            </x-page-hero>

            @if (session('status'))
                <x-ui.success-alert :title="session('status')" />
            @endif

            <form id="{{ $formId }}" method="post" action="{{ route('clinical-records.store') }}" class="hidden">
                @csrf
            </form>

            <div class="space-y-6">
                @if ($errors->has('patient_id') || $errors->has('content') || ($errors->any() && ! $errors->hasAny(['audio', 'session_text', 'lgpd_audio_consent', 'approach', 'output_type', 'session_type'])))
                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->get('patient_id') as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                            @foreach ($errors->get('content') as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
                    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-400" aria-hidden="true">
                            <x-ui.icon name="users" class="h-4 w-4" />
                        </span>
                        {{ __('Paciente') }}
                    </h3>

                    <div class="mt-4">
                        <x-input-label for="patient_id" :value="__('Paciente')" class="text-slate-700 dark:text-slate-200" />
                        <select id="patient_id" name="patient_id" form="{{ $formId }}" class="{{ $inputBase }}" required>
                            <option value="">{{ __('Selecione…') }}</option>
                            @foreach ($patients as $patient)
                                <option value="{{ $patient->id }}" @selected((string) $selectedPatientId === (string) $patient->id)>{{ $patient->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('patient_id')" />
                    </div>
                </section>

                @if (Auth::user()?->canUseSubscriptionFeature('use_ai'))
                <x-clinical-record-ai-panel :patients="$patients" :patient-id="$selectedPatientId" />
                @endif

                <section id="conteudo-prontuario" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-5 py-4 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900/90">
                        <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300" aria-hidden="true">
                                <x-ui.icon name="document-text" class="h-4 w-4" />
                            </span>
                            {{ __('Conteúdo') }}
                        </h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ Auth::user()?->canUseSubscriptionFeature('use_ai') ? __('Escreva manualmente ou use o apoio da IA acima. Este conteúdo é guardado de forma encriptada.') : __('Escreva o registo clínico. Este conteúdo é guardado de forma encriptada.') }}</p>
                    </div>

                    <div class="p-5">
                        @if (session('ai_content') && ! old('content'))
                            <p class="mb-3 rounded-xl border border-indigo-200/80 bg-indigo-50 px-3 py-2 text-xs font-medium text-indigo-900 dark:border-indigo-800/60 dark:bg-indigo-950/40 dark:text-indigo-100">
                                {{ __('Conteúdo da IA inserido abaixo. Revise e edite antes de salvar.') }}
                            </p>
                        @endif
                        <x-input-label for="content" :value="__('Conteúdo (criptografado em repouso)')" class="text-slate-700 dark:text-slate-200" />
                        <textarea id="content" name="content" form="{{ $formId }}" rows="10" class="{{ $inputBase }}" required placeholder="{{ __('Escreva aqui o registo clínico…') }}">{{ $contentValue }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('content')" />
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                    <a href="{{ route('clinical-records.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">{{ __('Cancelar') }}</a>
                    <x-primary-button form="{{ $formId }}" class="justify-center sm:justify-start">{{ __('Salvar registro') }}</x-primary-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
