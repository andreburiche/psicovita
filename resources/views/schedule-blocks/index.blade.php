<x-app-layout>
    <x-slot name="header">{{ __('Bloqueios') }}</x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Bloqueios de agenda')"
                :subtitle="__('Marque intervalos indisponíveis — o sistema impede agendar sessões em conflito com estes períodos.')"
                icon="ban"
            >
                <x-slot name="eyebrow">
                    @if ($blocks->total() > 0)
                        {{ trans_choice(':count bloqueio registado|:count bloqueios registados', $blocks->total(), ['count' => number_format($blocks->total())]) }}
                    @else
                        {{ __('Gestão de disponibilidade') }}
                    @endif
                </x-slot>
                <x-slot name="actions">
                    <a
                        href="{{ route('schedule.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ __('Ver agenda') }}
                    </a>
                    <a
                        href="{{ route('schedule-blocks.create') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500"
                    >
                        <x-ui.icon name="plus" class="h-4 w-4 shrink-0" />
                        {{ __('Novo bloqueio') }}
                    </a>
                </x-slot>
            </x-page-hero>

            <div class="rounded-2xl border border-amber-200/80 bg-gradient-to-br from-amber-50/90 via-white to-orange-50/50 p-4 shadow-sm ring-1 ring-amber-100/80 dark:border-amber-900/40 dark:from-amber-950/30 dark:via-slate-900/80 dark:to-orange-950/20 dark:ring-amber-900/30 sm:p-5">
                <p class="flex items-start gap-3 text-sm leading-relaxed text-amber-950/90 dark:text-amber-100/90">
                    <x-ui.icon name="ban" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
                    <span>
                        {{ __('Use bloqueios para férias, formações, almoço ou indisponibilidade pontual. Ao criar ou mover uma sessão, o horário é validado automaticamente contra estes intervalos.') }}
                    </span>
                </p>
            </div>

            <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-5 py-4 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900/90">
                    <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300" aria-hidden="true">
                            <x-ui.icon name="clock" class="h-4 w-4" />
                        </span>
                        {{ __('Intervalos bloqueados') }}
                    </h2>
                </div>

                <ul class="divide-y divide-slate-100 dark:divide-slate-700/80" role="list">
                    @forelse ($blocks as $block)
                        @php
                            $start = substr((string) $block->start_time, 0, 5);
                            $end = substr((string) $block->end_time, 0, 5);
                            [$sh, $sm] = array_map('intval', explode(':', $start));
                            [$eh, $em] = array_map('intval', explode(':', $end));
                            $durationMin = max(0, ($eh * 60 + $em) - ($sh * 60 + $sm));
                            $hours = intdiv($durationMin, 60);
                            $mins = $durationMin % 60;
                            $durationLabel = $hours > 0
                                ? trim($hours.'h'.($mins ? ' '.$mins.'min' : ''))
                                : $mins.' min';

                            $isToday = $block->block_date->isToday();
                            $isPast = $block->block_date->isPast() && ! $isToday;
                            $isFuture = $block->block_date->isFuture();
                        @endphp
                        <li class="px-4 py-4 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40 sm:px-6">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-start gap-4">
                                    <div
                                        class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-gradient-to-br from-slate-100 to-slate-200/80 text-slate-800 ring-1 ring-slate-200/80 dark:from-slate-800 dark:to-slate-700 dark:text-slate-100 dark:ring-slate-600"
                                        aria-hidden="true"
                                    >
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $block->block_date->translatedFormat('M') }}</span>
                                        <span class="text-lg font-extrabold leading-none">{{ $block->block_date->format('d') }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($isToday)
                                                <x-ui.badge variant="warning">{{ __('Hoje') }}</x-ui.badge>
                                            @elseif ($isFuture)
                                                <x-ui.badge variant="info">{{ __('Próximo') }}</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="neutral">{{ __('Passado') }}</x-ui.badge>
                                            @endif
                                            <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $start }} — {{ $end }}</span>
                                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">({{ $durationLabel }})</span>
                                        </div>
                                        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                                            {{ $block->block_date->translatedFormat('l, d M Y') }}
                                        </p>
                                        @if ($block->reason)
                                            <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ $block->reason }}</p>
                                        @else
                                            <p class="mt-2 text-sm italic text-slate-400 dark:text-slate-500">{{ __('Sem motivo indicado') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-2 sm:pl-4">
                                    <a
                                        href="{{ route('schedule-blocks.edit', $block) }}"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-violet-700 shadow-sm transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:text-violet-300 dark:hover:bg-violet-950/30"
                                    >
                                        {{ __('Editar') }}
                                        <span aria-hidden="true">→</span>
                                    </a>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500" aria-hidden="true">
                                <x-ui.icon name="ban" class="h-8 w-8" />
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Nenhum bloqueio registado') }}</p>
                            <p class="mx-auto mt-2 max-w-sm text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                                {{ __('A sua agenda está totalmente disponível. Crie um bloqueio quando precisar reservar um intervalo.') }}
                            </p>
                            <a
                                href="{{ route('schedule-blocks.create') }}"
                                class="mt-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/20 transition hover:from-violet-500 hover:to-indigo-500"
                            >
                                <x-ui.icon name="plus" class="h-4 w-4 shrink-0" />
                                {{ __('Criar primeiro bloqueio') }}
                            </a>
                        </li>
                    @endforelse
                </ul>
            </section>

            @if ($blocks->hasPages())
                <div class="flex justify-center sm:justify-end">
                    {{ $blocks->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
