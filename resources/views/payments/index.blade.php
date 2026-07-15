<x-app-layout>
    <x-slot name="header">{{ __('Financeiro') }}</x-slot>

    <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
        <x-page-hero :title="__('Financeiro')" :subtitle="__('Pagamentos associados aos seus pacientes.')" icon="currency" iconTone="teal">
            <x-slot name="actions">
                <a
                    href="{{ route('payments.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('Novo pagamento') }}
                </a>
            </x-slot>
        </x-page-hero>

        <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-emerald-50/20 to-teal-50/30 p-1 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100/70 dark:border-slate-700/80 dark:from-slate-900 dark:via-slate-900 dark:to-emerald-950/20 dark:ring-slate-700/50">
            <form method="get" action="{{ route('payments.index') }}" class="space-y-4 p-4 sm:p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <x-ui.section-heading icon="filter" icon-tone="emerald" :title="__('Filtros')" class="flex-1" />
                    @if ($filtersActive ?? false)
                        <a href="{{ route('payments.index') }}" class="text-xs font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400">{{ __('Limpar filtros') }}</a>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                    <div class="sm:col-span-1 lg:col-span-3">
                        <label for="filter-status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Status') }}</label>
                        <select
                            id="filter-status"
                            name="status"
                            class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-3 pr-8 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                        >
                            <option value="">{{ __('Todos') }}</option>
                            @foreach (\App\Enums\PaymentStatus::cases() as $st)
                                <option value="{{ $st->value }}" @selected(old('status', request('status')) === $st->value)>{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-1 lg:col-span-4">
                        <label for="filter-patient" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Paciente') }}</label>
                        <select
                            id="filter-patient"
                            name="patient_id"
                            class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-3 pr-8 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                        >
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($patients as $p)
                                <option value="{{ $p->id }}" @selected((string) old('patient_id', request('patient_id')) === (string) $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-5">
                        <label for="filter-q" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Buscar por nome do paciente') }}</label>
                        <input
                            id="filter-q"
                            type="search"
                            name="q"
                            value="{{ old('q', request('q')) }}"
                            placeholder="{{ __('Nome…') }}"
                            class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                        />
                    </div>

                    <div class="sm:col-span-1 lg:col-span-3">
                        <label for="filter-from" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Desde') }}</label>
                        <input
                            id="filter-from"
                            type="date"
                            name="from"
                            value="{{ old('from', request('from')) }}"
                            class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                        />
                    </div>

                    <div class="sm:col-span-1 lg:col-span-3">
                        <label for="filter-to" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Até') }}</label>
                        <input
                            id="filter-to"
                            type="date"
                            name="to"
                            value="{{ old('to', request('to')) }}"
                            class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                        />
                    </div>

                    <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-6 lg:justify-end">
                        <button
                            type="submit"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 text-sm font-semibold text-white shadow-md transition hover:bg-slate-800 dark:bg-emerald-700 dark:hover:bg-emerald-600"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v9.448a2.25 2.25 0 01-1.272 2.03l-7.5 4.608a2.25 2.25 0 01-2.028 0l-7.5-4.608A2.25 2.25 0 013 14.222V4.778c0-.54.384-1.006.917-1.096C7.545 3.232 10.245 3 13 3z" />
                            </svg>
                            {{ __('Aplicar filtros') }}
                        </button>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100" role="alert">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </form>
        </div>

        @if ($payments->total() > 0)
            <p class="text-center text-xs font-medium uppercase tracking-widest text-slate-400 dark:text-slate-500">
                @if ($payments->total() === 1)
                    {{ __('1 pagamento') }}
                @else
                    {{ __(':count pagamentos', ['count' => $payments->total()]) }}
                @endif
                @if (! empty($filtersActive))
                    <span class="text-emerald-600/90 dark:text-emerald-400/90"> · {{ __('filtros ativos') }}</span>
                @endif
                @if ($payments->hasPages())
                    <span class="text-slate-300 dark:text-slate-600"> · </span>
                    {{ __('Página :current de :last', ['current' => $payments->currentPage(), 'last' => $payments->lastPage()]) }}
                @endif
            </p>
        @endif

        <div class="space-y-3">
            @forelse ($payments as $payment)
                @php
                    $badgeVariant = match ($payment->status) {
                        \App\Enums\PaymentStatus::Paid => 'success',
                        \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::PendingConfirmation => 'warning',
                        \App\Enums\PaymentStatus::Overdue => 'danger',
                        \App\Enums\PaymentStatus::Cancelled => 'neutral',
                        \App\Enums\PaymentStatus::Refunded => 'neutral',
                    };
                @endphp
                <article
                    class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100/80 transition duration-200 hover:-translate-y-0.5 hover:border-emerald-300/50 hover:shadow-lg hover:shadow-emerald-500/10 dark:border-slate-700 dark:bg-slate-900/70 dark:ring-slate-700/50 dark:hover:border-emerald-600/30 dark:hover:shadow-emerald-900/15 sm:p-5"
                >
                    <span class="absolute inset-y-4 left-0 w-1 rounded-r-full bg-gradient-to-b from-emerald-500 via-teal-500 to-emerald-600 opacity-0 transition group-hover:opacity-100" aria-hidden="true"></span>

                    <div class="flex flex-col gap-4 pl-0 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:pl-2">
                        <a href="{{ route('payments.show', $payment) }}" class="min-w-0 flex-1 outline-none ring-violet-500/30 focus-visible:rounded-xl focus-visible:ring-2">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="text-lg font-bold tabular-nums text-slate-900 dark:text-white">
                                    R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}
                                </span>
                                <x-ui.badge :variant="$badgeVariant">{{ $payment->status->label() }}</x-ui.badge>
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 text-sm text-slate-600 dark:text-slate-300">
                                <span class="inline-flex items-center gap-1.5 font-medium text-slate-800 dark:text-slate-100">
                                    <svg class="h-4 w-4 shrink-0 text-violet-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z" />
                                    </svg>
                                    {{ $payment->patient->name }}
                                </span>
                                <span class="text-slate-400 dark:text-slate-500">·</span>
                                <time datetime="{{ $payment->created_at->toIso8601String() }}" class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $payment->created_at->format('d/m/Y H:i') }}
                                </time>
                            </p>
                            @if ($payment->therapySession)
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('Sessão') }}:
                                    <span class="font-medium text-slate-600 dark:text-slate-300">{{ $payment->therapySession->session_date->format('d/m/Y') }}</span>
                                </p>
                            @endif
                        </a>

                        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3 sm:border-t-0 sm:pt-0 dark:border-slate-700">
                            <a
                                href="{{ route('payments.show', $payment) }}"
                                class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/80 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600 dark:hover:bg-slate-700"
                            >
                                {{ __('Detalhes') }}
                            </a>
                            @can('update', $payment)
                                <a
                                    href="{{ route('payments.edit', $payment) }}"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 ring-1 ring-violet-200/80 transition hover:bg-violet-100 dark:bg-violet-950/50 dark:text-violet-300 dark:ring-violet-800 dark:hover:bg-violet-900/50"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                    {{ __('Editar') }}
                                </a>
                            @endcan
                            @can('delete', $payment)
                                <x-confirm-form
                                    method="post"
                                    action="{{ route('payments.destroy', $payment) }}"
                                    :title="__('Remover pagamento?')"
                                    :message="__('Este registro financeiro será excluído permanentemente.')"
                                    :hint="__('Esta ação não pode ser desfeita.')"
                                    :confirm-label="__('Sim, remover')"
                                    variant="danger"
                                    :validate="false"
                                    class="inline"
                                >
                                    @csrf
                                    @method('delete')
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 ring-1 ring-rose-200/80 transition hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900 dark:hover:bg-rose-950/70"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.038-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                        {{ __('Excluir') }}
                                    </button>
                                </x-confirm-form>
                            @endcan
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-20 text-center dark:border-slate-600 dark:bg-slate-900/40">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-950 dark:to-teal-950">
                        <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0015.797 2.101c.738 0 1.374-.278 1.825-.896a3.75 3.75 0 00.125-4.45 3.75 3.75 0 00-3.546-2.209H2.25M2.25 12V9.75A2.25 2.25 0 014.5 7.5h15A2.25 2.25 0 0121.75 9.75V12m-9.303 3.75c.866 0 1.65-.318 2.25-.84M12 15.75c-.866 0-1.65-.318-2.25-.84m0 0c-.866 0-1.65-.318-2.25-.84M12 15.75V18m0 0h.008v.008H12V18z" />
                        </svg>
                    </div>
                    <p class="text-base font-semibold text-slate-700 dark:text-slate-200">{{ __('Nenhum pagamento registrado.') }}</p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('Registe o primeiro pagamento para acompanhar o financeiro.') }}</p>
                    <a
                        href="{{ route('payments.create') }}"
                        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-violet-500 hover:to-indigo-500"
                    >
                        {{ __('Novo pagamento') }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            @endforelse
        </div>

        @if ($payments->hasPages())
            <div class="flex justify-center border-t border-slate-100 pt-6 dark:border-slate-800">
                <div class="text-sm text-slate-500 [&_a]:font-medium [&_a]:text-violet-600 [&_a:hover]:text-violet-500 dark:[&_a]:text-violet-400">
                    {{ $payments->links() }}
                </div>
            </div>
        @elseif ($payments->total() > 0)
            <p class="text-center text-xs text-slate-400 dark:text-slate-500">{{ __('Todos os resultados estão visíveis.') }}</p>
        @endif
    </div>
</x-app-layout>
