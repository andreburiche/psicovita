@php
    $patient = $record->patient;
    $contentLength = mb_strlen((string) $record->content);
    $updatedRecently = $record->updated_at && $record->updated_at->gt($record->created_at);
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Prontuário') }}</x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Entrada de prontuário')"
                :subtitle="__('Registo clínico de :patient — conteúdo sensível protegido e com auditoria de acesso (LGPD).', ['patient' => $patient->name])"
                icon="document"
            >
                <x-slot name="eyebrow">
                    {{ __('Registo #:id', ['id' => $record->id]) }}
                    · {{ $record->created_at->translatedFormat('d M Y, H:i') }}
                </x-slot>
                <x-slot name="actions">
                    <a
                        href="{{ route('clinical-records.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ __('Voltar à lista') }}
                    </a>
                    <a
                        href="{{ route('clinical-records.edit', $record) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500"
                    >
                        <x-ui.icon name="clipboard" class="h-4 w-4 shrink-0" />
                        {{ __('Editar registo') }}
                    </a>
                </x-slot>
            </x-page-hero>

            {{-- Faixa de contexto --}}
            <section
                class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-slate-900 via-violet-950 to-indigo-950 p-6 shadow-xl shadow-violet-950/20 ring-1 ring-white/10 sm:p-8 dark:border-slate-700/50"
                aria-label="{{ __('Contexto do registo') }}"
            >
                <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-violet-500/20 blur-3xl" aria-hidden="true"></div>

                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4 sm:gap-5">
                        <div
                            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-400 to-indigo-600 text-xl font-bold text-white shadow-inner shadow-black/20 ring-2 ring-white/20 sm:h-20 sm:w-20 sm:text-2xl"
                            aria-hidden="true"
                        >
                            {{ mb_strtoupper(mb_substr($patient->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-200/90">{{ __('Utente') }}</p>
                            <p class="mt-1 truncate text-lg font-bold text-white sm:text-xl">{{ $patient->name }}</p>
                            <p class="mt-1 text-sm text-violet-100/80">
                                {{ __('Criado') }} {{ $record->created_at->diffForHumans() }}
                                @if ($updatedRecently)
                                    · {{ __('Atualizado') }} {{ $record->updated_at->diffForHumans() }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2">
                        <a
                            href="{{ route('patients.show', ['patient' => $patient, 'tab' => 'clinical-records']) }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20"
                        >
                            <x-ui.icon name="user" class="h-4 w-4 shrink-0" />
                            {{ __('Ficha do paciente') }}
                        </a>
                        <a
                            href="{{ route('clinical-records.create', ['patient_id' => $patient->id]) }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20"
                        >
                            <x-ui.icon name="plus" class="h-4 w-4 shrink-0" />
                            {{ __('Novo registo') }}
                        </a>
                    </div>
                </div>
            </section>

            <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
                {{-- Conteúdo --}}
                <div class="lg:col-span-8">
                    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-5 py-4 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900/90">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-600/10 text-violet-600 dark:bg-violet-400/15 dark:text-violet-300" aria-hidden="true">
                                            <x-ui.icon name="document-text" class="h-4 w-4" />
                                        </span>
                                        {{ __('Conteúdo clínico') }}
                                    </h2>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ trans_choice(':count carácter|:count caracteres', $contentLength, ['count' => number_format($contentLength)]) }}
                                    </p>
                                </div>
                                <x-ui.badge variant="info">{{ __('Encriptado') }}</x-ui.badge>
                            </div>
                        </div>

                        <div class="rounded-none border-b border-sky-100 bg-gradient-to-r from-sky-50/90 to-indigo-50/40 px-5 py-3.5 dark:border-sky-900/40 dark:from-sky-950/30 dark:to-indigo-950/20">
                            <p class="flex items-start gap-2.5 text-xs leading-relaxed text-sky-900/90 dark:text-sky-200/90">
                                <x-ui.icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-sky-600 dark:text-sky-400" />
                                {{ __('Cada visualização gera log de auditoria (LGPD). O conteúdo é armazenado de forma encriptada e acessível apenas ao profissional responsável.') }}
                            </p>
                        </div>

                        <div class="px-5 py-6 sm:px-6 sm:py-8">
                            <div class="prose prose-slate max-w-none dark:prose-invert">
                                <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800 dark:text-slate-100">{{ $record->content }}</p>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- Barra lateral --}}
                <aside class="space-y-6 lg:col-span-4">
                    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Metadados') }}</h2>
                        <dl class="mt-4 space-y-4">
                            <div>
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Identificador') }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">#{{ $record->id }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Criado em') }}</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-200">{{ $record->created_at->format('d/m/Y H:i') }}</dd>
                            </div>
                            @if ($updatedRecently)
                                <div>
                                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Última atualização') }}</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-200">{{ $record->updated_at->format('d/m/Y H:i') }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Utente') }}</dt>
                                <dd class="mt-1">
                                    <a href="{{ route('patients.show', $patient) }}" class="text-sm font-semibold text-violet-600 transition hover:text-violet-500 dark:text-violet-400">
                                        {{ $patient->name }}
                                    </a>
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Ações') }}</h2>
                        <ul class="mt-4 space-y-2" role="list">
                            <li>
                                <a
                                    href="{{ route('clinical-records.edit', $record) }}"
                                    class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 dark:hover:border-violet-500 dark:hover:bg-violet-950/30"
                                >
                                    <x-ui.icon name="clipboard" class="h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" />
                                    {{ __('Editar conteúdo') }}
                                </a>
                            </li>
                            <li>
                                <a
                                    href="{{ route('patients.show', $patient) }}"
                                    class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100 dark:hover:border-violet-500 dark:hover:bg-violet-950/30"
                                >
                                    <x-ui.icon name="user" class="h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" />
                                    {{ __('Abrir ficha do utente') }}
                                </a>
                            </li>
                        </ul>

                        <div class="mt-6 border-t border-slate-100 pt-5 dark:border-slate-700">
                            <x-confirm-form
                                method="post"
                                action="{{ route('clinical-records.destroy', $record) }}"
                                :title="__('Remover registro?')"
                                :message="__('O conteúdo deste prontuário será excluído permanentemente. Esta ação não pode ser desfeita.')"
                                :confirm-label="__('Sim, remover')"
                                variant="danger"
                                :validate="false"
                            >
                                @csrf
                                @method('delete')
                                <button
                                    type="submit"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-800 transition hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-200 dark:hover:bg-rose-950/50"
                                >
                                    {{ __('Excluir registo') }}
                                </button>
                            </x-confirm-form>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
