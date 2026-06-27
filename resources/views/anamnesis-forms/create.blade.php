<x-app-layout>
    <x-slot name="header">{{ __('Novo modelo de anamnese') }}</x-slot>

    <div class="mx-auto max-w-5xl space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <x-page-hero :title="__('Novo modelo de anamnese')" :subtitle="__('Monte os campos (máscaras e validações padrão são aplicadas ao escolher o tipo).')" icon="clipboard">
            <x-slot name="actions">
                <a
                    href="{{ route('anamnesis-forms.index') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                    {{ __('Voltar') }}
                </a>
            </x-slot>
        </x-page-hero>

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                <p class="font-semibold">{{ __('Revise os campos antes de salvar') }}</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
            <div class="flex flex-col gap-3 border-b border-slate-100 bg-gradient-to-r from-violet-50/90 to-indigo-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:from-violet-950/50 dark:to-indigo-950/40">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Dicas rápidas') }}</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Para o modelo ficar consistente e fácil de usar na ficha do paciente.') }}</p>
                </div>
                <a href="{{ route('patients.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-violet-700 hover:text-violet-600 dark:text-violet-300">
                    {{ __('Ver pacientes') }} <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="p-5 sm:p-6">
                <div class="grid gap-4 lg:grid-cols-12">
                    <div class="lg:col-span-7">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Como nomear a “Chave (slug)”') }}</p>
                        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-slate-600 dark:text-slate-400">
                            <li>{{ __('Use minúsculas e underscore: ex. “historico_familiar”.') }}</li>
                            <li>{{ __('Evite acentos, espaços e caracteres especiais.') }}</li>
                            <li>{{ __('A chave é usada para guardar/validar as respostas do paciente.') }}</li>
                        </ul>
                    </div>
                    <div class="lg:col-span-5">
                        <div class="rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-xs text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/35 dark:text-amber-100">
                            <span class="font-semibold">{{ __('LGPD') }}:</span>
                            {{ __('evite pedir dados desnecessários. Prefira campos objetivos e estritamente clínicos.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-violet-50/40 p-1 shadow-xl shadow-violet-900/5 ring-1 ring-violet-100/70 dark:border-slate-700/80 dark:from-slate-900 dark:via-slate-900 dark:to-violet-950/20 dark:shadow-black/20 dark:ring-violet-900/30">
            <div class="p-0">
                @include('anamnesis-forms.partials.form-builder', [
                    'submitAction' => route('anamnesis-forms.store'),
                    'httpMethod' => 'post',
                    'record' => null,
                    'initialQuestions' => $initialQuestions,
                    'fieldDefaultsJson' => $fieldDefaultsJson,
                    'submitLabel' => __('Salvar modelo'),
                ])
            </div>
        </div>
    </div>
</x-app-layout>
