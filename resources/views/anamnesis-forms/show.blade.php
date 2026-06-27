<x-app-layout>
    <x-slot name="header">{{ __('Pré-visualização') }} — {{ $form->title }}</x-slot>

    <div class="mx-auto max-w-5xl space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <x-page-hero :title="__('Pré-visualização')" :subtitle="$form->description ?: __('Visualização de teste — dados não são gravados.')" icon="eye">
            <x-slot name="actions">
                <a
                    href="{{ route('anamnesis-forms.index') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                    {{ __('Voltar') }}
                </a>
                <a
                    href="{{ route('anamnesis-forms.edit', $form) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500"
                >
                    {{ __('Editar modelo') }}
                </a>
            </x-slot>
        </x-page-hero>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
            {{ __('Visualização de teste — dados não são gravados. LGPD: use apenas em ambiente autorizado.') }}
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
            <div class="border-b border-slate-100 bg-gradient-to-r from-violet-50/90 to-indigo-50/60 px-5 py-4 dark:border-slate-700 dark:from-violet-950/50 dark:to-indigo-950/40">
                <h2 class="text-base font-bold tracking-tight text-slate-900 dark:text-white">{{ $form->title }}</h2>
                @if ($form->description)
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ $form->description }}</p>
                @endif
            </div>

            <div class="p-5 sm:p-6">
                <div class="space-y-6">
                    @foreach ($form->questions as $question)
                        <x-anamnesis-field-input :question="$question" name-prefix="preview" />
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
