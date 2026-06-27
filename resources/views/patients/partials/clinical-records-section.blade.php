@props([
    'patient',
    'clinicalRecords',
])

@php
    $recordCount = $clinicalRecords->total();
@endphp

<section
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60"
    aria-label="{{ __('Prontuário do paciente') }}"
>
    <div class="flex flex-col gap-4 border-b border-slate-100 bg-gradient-to-r from-violet-50/80 to-indigo-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:from-violet-950/40 dark:to-indigo-950/30">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-violet-900 dark:text-violet-200">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-600/10 text-violet-600 dark:bg-violet-400/15 dark:text-violet-300" aria-hidden="true">
                        <x-ui.icon name="document-text" class="h-4 w-4" />
                    </span>
                    {{ __('Histórico do prontuário') }}
                </h3>
                @if ($recordCount > 0)
                    <span class="inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-700 dark:bg-violet-950 dark:text-violet-300">
                        {{ trans_choice(':count registro|:count registros', $recordCount, ['count' => $recordCount]) }}
                    </span>
                @endif
            </div>
            <p class="mt-1.5 text-xs text-slate-600 dark:text-slate-400">{{ __('Registros clínicos encriptados — acesso restrito ao profissional responsável.') }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a
                href="{{ route('clinical-records.create', ['patient_id' => $patient->id]) }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30 dark:bg-violet-500 dark:hover:bg-violet-400"
            >
                <x-ui.icon name="plus" class="h-3.5 w-3.5" />
                {{ __('Novo registro') }}
            </a>
            <a
                href="{{ route('clinical-records.index') }}"
                class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-violet-700 transition hover:border-violet-200 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:text-violet-300 dark:hover:bg-slate-700"
            >{{ __('Ver todos') }} →</a>
        </div>
    </div>

    <div class="p-4 sm:p-5">
        <ul class="space-y-3" role="list">
            @forelse ($clinicalRecords as $record)
                @php
                    $preview = \Illuminate\Support\Str::of((string) $record->content)->squish()->limit(160);
                @endphp
                <li>
                    <a
                        href="{{ route('clinical-records.show', $record) }}"
                        class="group block rounded-xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/50 p-4 transition hover:border-violet-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-violet-500/30 dark:border-slate-600 dark:from-slate-800/80 dark:to-slate-900/50 dark:hover:border-violet-600"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ $record->created_at->format('d/m/Y') }}
                                        <span class="font-normal text-slate-500 dark:text-slate-400">{{ $record->created_at->format('H:i') }}</span>
                                    </p>
                                    @if ($record->updated_at && $record->updated_at->gt($record->created_at))
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                            {{ __('Editado') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                    {{ $preview }}
                                </p>
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-1 self-center text-sm font-semibold text-violet-600 opacity-80 transition group-hover:opacity-100 dark:text-violet-400">
                                <span class="hidden sm:inline">{{ __('Abrir') }}</span>
                                <x-ui.icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </div>
                    </a>
                </li>
            @empty
                <li class="rounded-xl border border-dashed border-violet-200/70 bg-violet-50/40 px-6 py-12 text-center dark:border-violet-900/50 dark:bg-violet-950/20">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-400" aria-hidden="true">
                        <x-ui.icon name="document-text" class="h-6 w-6" />
                    </span>
                    <p class="mt-4 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Nenhum registro no prontuário') }}</p>
                    <p class="mx-auto mt-1.5 max-w-sm text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        {{ __('Crie o primeiro registro clínico deste paciente. Pode usar o apoio da IA na página de criação.') }}
                    </p>
                    <a
                        href="{{ route('clinical-records.create', ['patient_id' => $patient->id]) }}"
                        class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-500"
                    >
                        <x-ui.icon name="plus" class="h-4 w-4" />
                        {{ __('Criar registro') }}
                    </a>
                </li>
            @endforelse
        </ul>
    </div>

    <div class="border-t border-slate-100 px-4 pb-4 dark:border-slate-800 sm:px-5 sm:pb-5">
        <x-list-pagination
            :paginator="$clinicalRecords"
            :item-label="trans_choice('registro|registros', $clinicalRecords->total())"
            class="border-0 bg-transparent shadow-none ring-0 dark:bg-transparent"
        />
    </div>
</section>
