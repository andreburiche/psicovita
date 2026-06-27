@php
    $b = $block;
    $isEdit = $b !== null;
    $defaultDate = old('block_date', $b?->block_date?->format('Y-m-d') ?? now()->format('Y-m-d'));
    $defaultStart = old('start_time', $b ? substr((string) $b->start_time, 0, 5) : '12:00');
    $defaultEnd = old('end_time', $b ? substr((string) $b->end_time, 0, 5) : '13:00');
    $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
@endphp

@if ($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
    <div class="border-b border-slate-100 bg-gradient-to-r from-violet-50/90 via-indigo-50/50 to-white px-5 py-4 dark:border-slate-700 dark:from-violet-950/40 dark:via-indigo-950/30 dark:to-slate-900/90 sm:px-6">
        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">
            {{ $isEdit ? __('Dados do bloqueio') : __('Configurar novo bloqueio') }}
        </h2>
        <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
            {{ __('Preencha os três passos abaixo. O intervalo ficará indisponível para agendamento de sessões.') }}
        </p>
    </div>

    {{-- Passo 1 --}}
    <div class="px-5 py-5 sm:px-6 sm:py-6">
        <div class="flex gap-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white shadow-sm" aria-hidden="true">1</span>
            <div class="min-w-0 flex-1">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                    <x-ui.icon name="calendar" class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                    {{ __('Quando') }}
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Dia em que o intervalo fica bloqueado.') }}</p>
                <div class="mt-4 max-w-xs">
                    <x-input-label for="block_date" :value="__('Data')" class="text-slate-700 dark:text-slate-200" />
                    <input id="block_date" name="block_date" type="date" class="{{ $inputBase }}" value="{{ $defaultDate }}" required />
                    <x-input-error class="mt-2" :messages="$errors->get('block_date')" />
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-100 dark:border-slate-700/80" aria-hidden="true"></div>

    {{-- Passo 2 --}}
    <div class="px-5 py-5 sm:px-6 sm:py-6">
        <div class="flex gap-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-600 text-xs font-bold text-white shadow-sm" aria-hidden="true">2</span>
            <div class="min-w-0 flex-1">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                    <x-ui.icon name="clock" class="h-4 w-4 text-rose-600 dark:text-rose-400" />
                    {{ __('Horário') }}
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('O fim deve ser depois do início.') }}</p>
                <div class="mt-4 grid max-w-md gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="start_time" :value="__('Início')" class="text-slate-700 dark:text-slate-200" />
                        <input id="start_time" name="start_time" type="time" class="{{ $inputBase }}" value="{{ $defaultStart }}" required />
                        <x-input-error class="mt-2" :messages="$errors->get('start_time')" />
                    </div>
                    <div>
                        <x-input-label for="end_time" :value="__('Fim')" class="text-slate-700 dark:text-slate-200" />
                        <input id="end_time" name="end_time" type="time" class="{{ $inputBase }}" value="{{ $defaultEnd }}" required />
                        <x-input-error class="mt-2" :messages="$errors->get('end_time')" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-100 dark:border-slate-700/80" aria-hidden="true"></div>

    {{-- Passo 3 --}}
    <div class="px-5 py-5 sm:px-6 sm:py-6">
        <div class="flex gap-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-violet-600 text-xs font-bold text-white shadow-sm" aria-hidden="true">3</span>
            <div class="min-w-0 flex-1">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                    <x-ui.icon name="document-text" class="h-4 w-4 text-violet-600 dark:text-violet-400" />
                    {{ __('Motivo') }}
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold normal-case tracking-normal text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ __('opcional') }}</span>
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Facilita identificar o bloqueio na lista.') }}</p>
                <div class="mt-4">
                    <x-input-label for="reason" :value="__('Descrição')" class="text-slate-700 dark:text-slate-200" />
                    <input
                        id="reason"
                        name="reason"
                        type="text"
                        maxlength="255"
                        placeholder="{{ __('Ex.: Almoço, férias, supervisão…') }}"
                        class="{{ $inputBase }}"
                        value="{{ old('reason', $b?->reason) }}"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/40 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-5">
        <a
            href="{{ route('schedule-blocks.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
        >
            {{ __('Cancelar') }}
        </a>
        <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500"
        >
            <x-ui.icon name="check" class="h-4 w-4 shrink-0" />
            {{ $submit }}
        </button>
    </div>
</div>
