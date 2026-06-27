@php
    $start = substr((string) $block->start_time, 0, 5);
    $end = substr((string) $block->end_time, 0, 5);
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Editar bloqueio') }}</x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-2xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Editar bloqueio')"
                :subtitle="__('Altere data, horário ou motivo deste intervalo indisponível.')"
                icon="ban"
            >
                <x-slot name="eyebrow">
                    {{ $block->block_date->translatedFormat('d M Y') }} · {{ $start }} — {{ $end }}
                </x-slot>
                <x-slot name="actions">
                    <a
                        href="{{ route('schedule-blocks.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ __('Voltar') }}
                    </a>
                </x-slot>
            </x-page-hero>

            <form method="post" action="{{ route('schedule-blocks.update', $block) }}" class="space-y-6">
                @csrf
                @method('put')
                @include('schedule-blocks._form', ['block' => $block, 'submit' => __('Guardar alterações')])
            </form>

            <section class="overflow-hidden rounded-2xl border border-rose-200/80 bg-rose-50/50 p-5 dark:border-rose-900/40 dark:bg-rose-950/20 sm:p-6">
                <h2 class="text-xs font-bold uppercase tracking-wider text-rose-900 dark:text-rose-200">{{ __('Zona de risco') }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-rose-900/80 dark:text-rose-100/80">
                    {{ __('Remover o bloqueio liberta o intervalo para novas sessões. Esta ação não pode ser desfeita.') }}
                </p>
                <x-confirm-form
                    method="post"
                    action="{{ route('schedule-blocks.destroy', $block) }}"
                    :title="__('Excluir bloqueio?')"
                    :message="__('O intervalo deixará de estar bloqueado na agenda.')"
                    :confirm-label="__('Sim, excluir')"
                    variant="danger"
                    :validate="false"
                    class="mt-4"
                >
                    @csrf
                    @method('delete')
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-300 bg-white px-4 py-2.5 text-sm font-semibold text-rose-800 transition hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200 dark:hover:bg-rose-950/60 sm:w-auto"
                    >
                        {{ __('Excluir bloqueio') }}
                    </button>
                </x-confirm-form>
            </section>
        </div>
    </div>
</x-app-layout>
