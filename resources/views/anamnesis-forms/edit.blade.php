<x-app-layout>
    <x-slot name="header">{{ __('Editar modelo') }}</x-slot>

    <div class="mx-auto max-w-5xl space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <x-page-hero :title="__('Editar modelo de anamnese')" :subtitle="__('Atualize os campos e a ordem do modelo.')" icon="clipboard">
            <x-slot name="actions">
                <a
                    href="{{ route('anamnesis-forms.show', $form) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                    {{ __('Pré-visualizar') }}
                </a>
            </x-slot>
        </x-page-hero>

        @include('anamnesis-forms.partials.form-builder', [
            'submitAction' => route('anamnesis-forms.update', $form),
            'httpMethod' => 'put',
            'record' => $form,
            'initialQuestions' => $initialQuestions,
            'fieldDefaultsJson' => $fieldDefaultsJson,
            'submitLabel' => __('Atualizar modelo'),
        ])
    </div>
</x-app-layout>
