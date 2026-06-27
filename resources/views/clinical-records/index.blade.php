<x-app-layout>
    <x-slot name="header">{{ __('Prontuário') }}</x-slot>

    <div class="mx-auto max-w-7xl space-y-6">
        <x-page-hero :title="__('Prontuário')" :subtitle="__('Registros clínicos por paciente (conteúdo sensível — LGPD).')" icon="document">
            <x-slot name="actions">
                <a
                    href="{{ route('clinical-records.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/20 transition hover:from-violet-500 hover:to-indigo-500"
                >
                    <x-ui.icon name="plus" class="h-5 w-5" />
                    {{ __('Novo registro') }}
                </a>
            </x-slot>
        </x-page-hero>

        <section class="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-br from-white via-slate-50/90 to-violet-50/35 p-4 shadow-lg shadow-violet-950/5 ring-1 ring-slate-200/70 dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-violet-950/25 dark:shadow-black/25 dark:ring-slate-700/80 sm:p-6">
            <div class="pointer-events-none absolute -right-20 -top-20 h-44 w-44 rounded-full bg-violet-400/10 blur-3xl dark:bg-violet-500/10" aria-hidden="true"></div>

            <div class="relative mb-4 flex flex-wrap items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 text-white shadow-md shadow-violet-600/25 dark:shadow-violet-900/40" aria-hidden="true">
                    <x-ui.icon name="document-text" class="h-5 w-5" />
                </span>
                <div>
                    <h2 class="text-base font-bold tracking-tight text-slate-900 dark:text-white">{{ __('Lista de registros') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ __('Dados clínicos — acesso restrito ao profissional responsável.') }}</p>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-slate-200/90 bg-white/95 shadow-inner dark:border-slate-700 dark:bg-slate-950/50">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[420px] text-left text-sm">
                        <thead class="sticky top-0 z-[1] border-b border-slate-200/90 bg-slate-50/95 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/95">
                            <tr class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <th scope="col" class="px-4 py-3.5 pl-5">{{ __('Paciente') }}</th>
                                <th scope="col" class="px-4 py-3.5">{{ __('Criado em') }}</th>
                                <th scope="col" class="px-4 py-3.5 pr-5 text-right">{{ __('Ações') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($records as $record)
                                <tr class="transition-colors hover:bg-violet-50/70 dark:hover:bg-violet-950/20">
                                    <td class="px-4 py-3.5 pl-5 align-middle">
                                        <div class="flex max-w-md items-center gap-3">
                                            <x-patient-avatar :patient="$record->patient" size="sm" class="ring-1 ring-violet-200/80 dark:ring-violet-700/50" />
                                            <span class="min-w-0 truncate font-medium text-slate-900 dark:text-slate-100" title="{{ $record->patient->name }}">{{ $record->patient->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 align-middle">
                                        <div class="flex items-center gap-2 tabular-nums text-slate-600 dark:text-slate-300">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400" aria-hidden="true">
                                                <x-ui.icon name="calendar" class="h-4 w-4" />
                                            </span>
                                            <span>{{ $record->created_at->format('d/m/Y') }}</span>
                                            <span class="text-slate-400 dark:text-slate-500">{{ $record->created_at->format('H:i') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 pr-5 align-middle text-right">
                                        <a
                                            href="{{ route('clinical-records.show', $record) }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-transparent text-violet-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:text-violet-400 dark:hover:border-violet-800 dark:hover:bg-violet-950/80 dark:hover:text-violet-300 dark:focus:ring-offset-slate-900"
                                            title="{{ __('Abrir registro') }}"
                                        >
                                            <span class="sr-only">{{ __('Abrir') }}</span>
                                            <x-ui.icon name="arrow-up-right" class="h-5 w-5" />
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-14 text-center">
                                        <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                                <x-ui.icon name="document-text" class="h-7 w-7" />
                                            </span>
                                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Nenhum registro de prontuário.') }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Crie o primeiro registro pelo botão acima ou pela ficha do paciente.') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <x-list-pagination
            :paginator="$records"
            :item-label="trans_choice('registro|registros', $records->total())"
        />
    </div>
</x-app-layout>
