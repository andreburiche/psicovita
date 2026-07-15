@props([
    'patient',
    'payments',
    'paymentStats',
])

@php
    $paymentCount = $payments->total();
@endphp

<section
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60"
    aria-label="{{ __('Histórico financeiro do paciente') }}"
>
    <div class="flex flex-col gap-4 border-b border-slate-100 bg-gradient-to-r from-emerald-50/80 to-teal-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:from-emerald-950/40 dark:to-teal-950/30">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-200">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600/10 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-300" aria-hidden="true">
                        <x-ui.icon name="currency" class="h-4 w-4" />
                    </span>
                    {{ __('Histórico de pagamentos') }}
                </h3>
                @if ($paymentCount > 0)
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        {{ trans_choice(':count pagamento|:count pagamentos', $paymentCount, ['count' => $paymentCount]) }}
                    </span>
                @endif
            </div>
            <p class="mt-1.5 text-xs text-slate-600 dark:text-slate-400">{{ __('Registros financeiros vinculados a este paciente e às sessões.') }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @can('create', \App\Models\Payment::class)
                <a
                    href="{{ route('payments.create', ['patient_id' => $patient->id]) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                >
                    <x-ui.icon name="plus" class="h-3.5 w-3.5" />
                    {{ __('Novo pagamento') }}
                </a>
            @endcan
            <a
                href="{{ route('payments.index', ['patient_id' => $patient->id]) }}"
                class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-emerald-700 transition hover:border-emerald-200 hover:bg-emerald-50 dark:border-slate-600 dark:bg-slate-800 dark:text-emerald-300 dark:hover:bg-slate-700"
            >{{ __('Ver no financeiro') }} →</a>
        </div>
    </div>

    <div class="grid gap-3 border-b border-slate-100 bg-slate-50/60 p-4 sm:grid-cols-3 dark:border-slate-700 dark:bg-slate-900/40">
        <div class="rounded-xl border border-emerald-200/70 bg-white p-3 dark:border-emerald-900/40 dark:bg-slate-800/80">
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Total recebido') }}</p>
            <p class="mt-1 text-lg font-bold tabular-nums text-emerald-700 dark:text-emerald-300">R$ {{ number_format($paymentStats['paid_total'], 2, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-amber-200/70 bg-white p-3 dark:border-amber-900/40 dark:bg-slate-800/80">
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Pendente / em aberto') }}</p>
            <p class="mt-1 text-lg font-bold tabular-nums text-amber-700 dark:text-amber-300">R$ {{ number_format($paymentStats['pending_total'], 2, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-rose-200/70 bg-white p-3 dark:border-rose-900/40 dark:bg-slate-800/80">
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Em atraso') }}</p>
            <p class="mt-1 text-lg font-bold tabular-nums text-rose-700 dark:text-rose-300">{{ $paymentStats['overdue_count'] }}</p>
        </div>
    </div>

    <div class="p-4 sm:p-5">
        <ul class="space-y-3" role="list">
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
                <li>
                    <a
                        href="{{ route('payments.show', $payment) }}"
                        class="group block rounded-xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/50 p-4 transition hover:border-emerald-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-slate-600 dark:from-slate-800/80 dark:to-slate-900/50 dark:hover:border-emerald-700"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-lg font-bold tabular-nums text-slate-900 dark:text-white">
                                        R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}
                                    </span>
                                    <x-ui.badge :variant="$badgeVariant">{{ $payment->status->label() }}</x-ui.badge>
                                    <x-ui.badge variant="neutral">{{ $payment->payment_method->label() }}</x-ui.badge>
                                </div>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                    <time datetime="{{ $payment->created_at->toIso8601String() }}">{{ $payment->created_at->format('d/m/Y H:i') }}</time>
                                    @if ($payment->therapySession)
                                        <span class="text-slate-400 dark:text-slate-500"> · </span>
                                        {{ __('Sessão') }} {{ $payment->therapySession->session_date->format('d/m/Y') }}
                                    @endif
                                </p>
                                @if ($payment->notes)
                                    <p class="mt-2 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{{ $payment->notes }}</p>
                                @endif
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-1 self-center text-sm font-semibold text-emerald-600 opacity-80 transition group-hover:opacity-100 dark:text-emerald-400">
                                <span class="hidden sm:inline">{{ __('Abrir') }}</span>
                                <x-ui.icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </div>
                    </a>
                </li>
            @empty
                <li class="rounded-xl border border-dashed border-emerald-200/70 bg-emerald-50/40 px-6 py-12 text-center dark:border-emerald-900/50 dark:bg-emerald-950/20">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400" aria-hidden="true">
                        <x-ui.icon name="currency" class="h-6 w-6" />
                    </span>
                    <p class="mt-4 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Nenhum pagamento registrado') }}</p>
                    <p class="mx-auto mt-1.5 max-w-sm text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        {{ __('Registe pagamentos deste paciente para acompanhar receitas, pendências e sessões vinculadas.') }}
                    </p>
                    @can('create', \App\Models\Payment::class)
                        <a
                            href="{{ route('payments.create', ['patient_id' => $patient->id]) }}"
                            class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500"
                        >
                            <x-ui.icon name="plus" class="h-4 w-4" />
                            {{ __('Registrar pagamento') }}
                        </a>
                    @endcan
                </li>
            @endforelse
        </ul>
    </div>

    <div class="border-t border-slate-100 px-4 pb-4 dark:border-slate-800 sm:px-5 sm:pb-5">
        <x-list-pagination
            :paginator="$payments"
            :item-label="trans_choice('pagamento|pagamentos', $payments->total())"
            class="border-0 bg-transparent shadow-none ring-0 dark:bg-transparent"
        />
    </div>
</section>
