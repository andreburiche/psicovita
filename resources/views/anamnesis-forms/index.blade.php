<x-app-layout>
    <x-slot name="header">{{ __('Modelos de anamnese') }}</x-slot>

    <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
        <x-page-hero :title="__('Anamnese')" :subtitle="__('Defina perguntas com máscaras e validações automáticas.')" icon="clipboard">
            <x-slot name="actions">
                <a href="{{ route('anamnesis-forms.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500">{{ __('Novo modelo') }}</a>
            </x-slot>
        </x-page-hero>

        <div class="space-y-3">
            @forelse ($forms as $f)
                <a
                    href="{{ route('anamnesis-forms.show', $f) }}"
                    class="group relative flex flex-col gap-4 overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100/80 transition duration-200 hover:-translate-y-0.5 hover:border-violet-300/50 hover:shadow-lg hover:shadow-violet-500/10 dark:border-slate-700 dark:bg-slate-900/70 dark:ring-slate-700/50 dark:hover:border-violet-500/30 dark:hover:shadow-violet-900/20 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:p-5"
                >
                    <span class="absolute inset-y-4 left-0 w-1 rounded-r-full bg-gradient-to-b from-violet-500 via-indigo-500 to-violet-600 opacity-0 transition group-hover:opacity-100" aria-hidden="true"></span>

                    <div class="min-w-0 flex-1 pl-0 sm:pl-2 flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700 ring-1 ring-violet-200/80 dark:bg-violet-950 dark:text-violet-300 dark:ring-violet-800" aria-hidden="true">
                            <x-ui.icon name="clipboard" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                        <p class="truncate text-base font-semibold text-slate-900 group-hover:text-violet-700 dark:text-white dark:group-hover:text-violet-300">
                            {{ $f->title }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ __(':count campos', ['count' => $f->questions_count]) }}
                            @if ($f->updated_at)
                                <span class="text-slate-300 dark:text-slate-600"> · </span>
                                {{ __('Atualizado em :date', ['date' => $f->updated_at->format('d/m/Y')]) }}
                            @endif
                        </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3 sm:border-t-0 sm:pt-0 dark:border-slate-700">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/80 transition group-hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600 dark:group-hover:bg-slate-700">
                            {{ __('Pré-visualizar') }}
                            <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </span>
                        <span class="inline-flex items-center rounded-full bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 ring-1 ring-violet-200/80 transition group-hover:bg-violet-100 dark:bg-violet-950/50 dark:text-violet-300 dark:ring-violet-800 dark:group-hover:bg-violet-900/40">
                            {{ __('Editar') }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-20 text-center dark:border-slate-600 dark:bg-slate-900/40">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-100 to-indigo-100 dark:from-violet-950 dark:to-indigo-950">
                        <svg class="h-8 w-8 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                    </div>
                    <p class="text-base font-semibold text-slate-700 dark:text-slate-200">{{ __('Nenhum modelo ainda.') }}</p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('Crie o primeiro modelo para usar na ficha do paciente.') }}</p>
                    <a href="{{ route('anamnesis-forms.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-violet-500 hover:to-indigo-500">
                        {{ __('Novo modelo') }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            @endforelse
        </div>

        @if ($forms->hasPages())
            <div class="flex justify-center border-t border-slate-100 pt-6 dark:border-slate-800">
                <div class="text-sm text-slate-500 [&_a]:font-medium [&_a]:text-violet-600 [&_a:hover]:text-violet-500 dark:[&_a]:text-violet-400">
                    {{ $forms->links() }}
                </div>
            </div>
        @else
            <div class="text-center text-xs text-slate-400 dark:text-slate-500">{{ __('Todos os resultados estão visíveis.') }}</div>
        @endif
    </div>
</x-app-layout>
