<x-app-layout>
    <x-slot name="header">{{ __('Editar prontuário') }}</x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Editar entrada de prontuário')"
                :subtitle="__('Atualize o conteúdo clínico. O texto permanece encriptado em repouso (LGPD).')"
                icon="document"
            >
                <x-slot name="eyebrow">{{ $record->patient->name }}</x-slot>
                <x-slot name="actions">
                    <a
                        href="{{ route('clinical-records.show', $record) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ __('Ver registro') }}
                    </a>
                </x-slot>
            </x-page-hero>

            <form method="post" action="{{ route('clinical-records.update', $record) }}" class="space-y-6 rounded-2xl border border-slate-200/90 bg-white p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60 sm:p-8">
                @csrf
                @method('put')

                <div>
                    <x-input-label for="content" :value="__('Conteúdo')" />
                    <textarea id="content" name="content" rows="10" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" required>{{ old('content', $record->content) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('content')" />
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('clinical-records.show', $record) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">{{ __('Cancelar') }}</a>
                    <x-primary-button>{{ __('Guardar alterações') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
