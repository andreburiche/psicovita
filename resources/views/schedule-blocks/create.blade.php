<x-app-layout>
    <x-slot name="header">{{ __('Novo bloqueio') }}</x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Novo bloqueio')"
                :subtitle="__('Reserve um intervalo na agenda em três passos simples: data, horário e motivo.')"
                icon="ban"
            >
                <x-slot name="actions">
                    <a
                        href="{{ route('schedule.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ __('Ver agenda') }}
                    </a>
                    <a
                        href="{{ route('schedule-blocks.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ __('Voltar') }}
                    </a>
                </x-slot>
            </x-page-hero>

            <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
                <div class="lg:col-span-8">
                    <form method="post" action="{{ route('schedule-blocks.store') }}" class="space-y-6">
                        @csrf
                        @include('schedule-blocks._form', ['block' => null, 'submit' => __('Criar bloqueio')])
                    </form>
                </div>

                <aside class="space-y-6 lg:col-span-4">
                    {{-- Atalhos de horário --}}
                    <section
                        class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60"
                        x-data="{
                            apply(start, end, reason = '') {
                                const s = document.getElementById('start_time');
                                const e = document.getElementById('end_time');
                                const r = document.getElementById('reason');
                                if (s) s.value = start;
                                if (e) e.value = end;
                                if (r && reason) r.value = reason;
                            },
                        }"
                    >
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Atalhos rápidos') }}</h2>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('Preenchem início, fim e motivo. Ajuste a data no passo 1.') }}</p>
                        <ul class="mt-4 space-y-2" role="list">
                            <li>
                                <button
                                    type="button"
                                    @click="apply('12:00', '13:00', @js(__('Almoço')))"
                                    class="flex w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-left text-sm font-semibold text-slate-800 transition hover:border-amber-300 hover:bg-amber-50 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 dark:hover:border-amber-700 dark:hover:bg-amber-950/30"
                                >
                                    <span>{{ __('Almoço') }}</span>
                                    <span class="shrink-0 text-xs font-medium text-slate-500 dark:text-slate-400">12:00 — 13:00</span>
                                </button>
                            </li>
                            <li>
                                <button
                                    type="button"
                                    @click="apply('08:00', '09:00', @js(__('Preparação consultório')))"
                                    class="flex w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-left text-sm font-semibold text-slate-800 transition hover:border-sky-300 hover:bg-sky-50 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 dark:hover:border-sky-700 dark:hover:bg-sky-950/30"
                                >
                                    <span>{{ __('Manhã') }}</span>
                                    <span class="shrink-0 text-xs font-medium text-slate-500 dark:text-slate-400">08:00 — 09:00</span>
                                </button>
                            </li>
                            <li>
                                <button
                                    type="button"
                                    @click="apply('17:00', '18:00', @js(__('Encerramento')))"
                                    class="flex w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-left text-sm font-semibold text-slate-800 transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30"
                                >
                                    <span>{{ __('Fim do dia') }}</span>
                                    <span class="shrink-0 text-xs font-medium text-slate-500 dark:text-slate-400">17:00 — 18:00</span>
                                </button>
                            </li>
                            <li>
                                <button
                                    type="button"
                                    @click="apply('09:00', '18:00', @js(__('Dia indisponível')))"
                                    class="flex w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-left text-sm font-semibold text-slate-800 transition hover:border-rose-300 hover:bg-rose-50 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 dark:hover:border-rose-800 dark:hover:bg-rose-950/30"
                                >
                                    <span>{{ __('Dia inteiro') }}</span>
                                    <span class="shrink-0 text-xs font-medium text-slate-500 dark:text-slate-400">09:00 — 18:00</span>
                                </button>
                            </li>
                        </ul>
                    </section>

                    {{-- Dicas --}}
                    <section class="rounded-2xl border border-sky-200/80 bg-gradient-to-br from-sky-50/90 via-white to-indigo-50/40 p-5 shadow-sm ring-1 ring-sky-100/80 dark:border-sky-900/40 dark:from-sky-950/30 dark:via-slate-900/80 dark:to-indigo-950/20 dark:ring-sky-900/30">
                        <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-sky-900 dark:text-sky-200">
                            <x-ui.icon name="shield" class="h-4 w-4 shrink-0" />
                            {{ __('Como funciona') }}
                        </h2>
                        <ul class="mt-3 space-y-2.5 text-xs leading-relaxed text-sky-950/85 dark:text-sky-100/90" role="list">
                            <li class="flex gap-2">
                                <span class="font-bold text-sky-700 dark:text-sky-300" aria-hidden="true">·</span>
                                {{ __('Sessões novas ou movidas para este intervalo são rejeitadas automaticamente.') }}
                            </li>
                            <li class="flex gap-2">
                                <span class="font-bold text-sky-700 dark:text-sky-300" aria-hidden="true">·</span>
                                {{ __('Pode criar vários bloqueios no mesmo dia com horários diferentes.') }}
                            </li>
                            <li class="flex gap-2">
                                <span class="font-bold text-sky-700 dark:text-sky-300" aria-hidden="true">·</span>
                                {{ __('O motivo aparece na lista para consulta rápida.') }}
                            </li>
                        </ul>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
