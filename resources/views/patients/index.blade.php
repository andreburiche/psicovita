<x-app-layout>
    <x-slot name="header">{{ __('Pacientes') }}</x-slot>

    <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
        <x-page-hero :title="__('Pacientes')" :subtitle="__('Lista de pacientes com busca rápida por nome, e-mail ou telefone.')" icon="users">
            <x-slot name="actions">
                @if (! ($patientQuota['at_limit'] ?? false))
                    <a
                        href="{{ route('patients.create') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500"
                    >
                        <x-ui.icon name="plus" class="h-5 w-5" />
                        {{ __('Novo paciente') }}
                    </a>
                @else
                    <a
                        href="{{ route('subscription.checkout') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-rose-300 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm transition hover:bg-rose-50 dark:border-rose-800 dark:bg-slate-900 dark:text-rose-300 dark:hover:bg-rose-950/40"
                    >
                        {{ __('Actualizar plano') }}
                    </a>
                @endif
            </x-slot>
        </x-page-hero>

        <x-patient-quota-alert :quota="$patientQuota" />

        <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-violet-50/30 to-indigo-50/40 p-1 shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/60 dark:border-slate-700/80 dark:from-slate-900 dark:via-slate-900 dark:to-violet-950/30 dark:ring-violet-900/30">
            <form method="get" class="flex flex-col gap-3 p-3 sm:flex-row sm:items-stretch sm:gap-2">
                @if (request()->filled('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}" />
                @endif
                <div class="relative min-w-0 flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-violet-400">
                        <x-ui.icon name="search" class="h-5 w-5" />
                    </span>
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="{{ __('Buscar por nome, e-mail ou telefone…') }}"
                        class="h-11 w-full rounded-xl border-0 bg-white/90 py-2.5 pl-11 pr-4 text-sm text-slate-900 shadow-inner shadow-slate-200/50 ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/40 dark:bg-slate-800/90 dark:text-slate-100 dark:shadow-none dark:ring-slate-600"
                    />
                </div>
                <button
                    type="submit"
                    class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl bg-slate-900 px-6 text-sm font-semibold text-white shadow-md transition hover:bg-slate-800 dark:bg-violet-600 dark:hover:bg-violet-500"
                >
                    {{ __('Filtrar') }}
                </button>
            </form>
        </div>

        <div class="space-y-3">
            @forelse ($patients as $patient)
                <a
                    href="{{ route('patients.show', $patient) }}"
                    class="group relative flex flex-col gap-4 overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100/80 transition duration-200 hover:-translate-y-0.5 hover:border-violet-300/60 hover:shadow-lg hover:shadow-violet-500/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-500 sm:flex-row sm:items-center sm:gap-5 sm:p-5 dark:border-slate-700 dark:bg-slate-900/70 dark:ring-slate-700/50 dark:hover:border-violet-500/40 dark:hover:shadow-violet-900/20"
                >
                    <span class="absolute inset-y-3 left-0 w-1 rounded-r-full bg-gradient-to-b from-violet-500 via-indigo-500 to-violet-600 opacity-0 transition group-hover:opacity-100" aria-hidden="true"></span>

                    <div class="flex min-w-0 flex-1 items-center gap-4 pl-0 sm:pl-2">
                        <x-patient-avatar :patient="$patient" size="list" class="ring-2 ring-white dark:ring-slate-800" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-base font-semibold text-slate-900 group-hover:text-violet-700 dark:text-white dark:group-hover:text-violet-300">
                                {{ $patient->name }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">{{ __('Ficha do paciente') }}</p>
                        </div>
                    </div>

                    <div class="flex min-w-0 flex-1 flex-col gap-2 border-t border-slate-100 pt-4 text-sm sm:border-t-0 sm:border-l sm:pl-6 sm:pt-0 dark:border-slate-700">
                        @if ($patient->email)
                            <div class="flex items-start gap-2.5 text-slate-600 dark:text-slate-300">
                                <span class="mt-0.5 text-violet-400" aria-hidden="true">
                                    <x-ui.icon name="mail" class="h-4 w-4" />
                                </span>
                                <span class="min-w-0 break-all">{{ $patient->email }}</span>
                            </div>
                        @endif
                        @if ($patient->phone)
                            <div class="flex items-center gap-2.5 text-slate-600 dark:text-slate-300">
                                <span class="text-violet-400" aria-hidden="true">
                                    <x-ui.icon name="phone" class="h-4 w-4" />
                                </span>
                                <span>{{ $patient->phone }}</span>
                            </div>
                        @endif
                        @if (! $patient->email && ! $patient->phone)
                            <span class="text-sm italic text-slate-400">{{ __('Sem contato registrado') }}</span>
                        @endif
                    </div>

                    <div class="flex shrink-0 items-center justify-between gap-3 sm:flex-col sm:items-end sm:justify-center">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 ring-1 ring-violet-200/80 transition group-hover:bg-violet-100 dark:bg-violet-950/50 dark:text-violet-300 dark:ring-violet-800"
                        >
                            {{ __('Abrir ficha') }}
                            <x-ui.icon name="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" />
                        </span>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-20 text-center dark:border-slate-600 dark:bg-slate-900/40">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-100 to-indigo-100 dark:from-violet-950 dark:to-indigo-950">
                        <x-ui.icon name="users" class="h-8 w-8 text-violet-500 dark:text-violet-400" />
                    </div>
                    <p class="text-base font-semibold text-slate-700 dark:text-slate-200">{{ __('Nenhum paciente ainda.') }}</p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('Comece por criar o primeiro paciente.') }}</p>
                    <a
                        href="{{ route('patients.create') }}"
                        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-violet-500 hover:to-indigo-500"
                    >
                        {{ __('Criar paciente') }}
                        <x-ui.icon name="arrow-right" class="h-4 w-4" />
                    </a>
                </div>
            @endforelse
        </div>

        <x-list-pagination
            :paginator="$patients"
            :item-label="trans_choice('paciente|pacientes', $patients->total())"
        />
    </div>
</x-app-layout>
