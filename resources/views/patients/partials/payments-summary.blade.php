@props([
    'patient',
    'paymentsCount' => 0,
    'latestPayment' => null,
    'paymentStats' => [],
])

<section
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-slate-700/60"
    aria-label="{{ __('Resumo financeiro') }}"
>
    <div class="flex flex-col gap-4 border-b border-slate-100 bg-gradient-to-r from-emerald-50/80 to-teal-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:from-emerald-950/40 dark:to-teal-950/30">
        <div class="min-w-0">
            <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-200">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600/10 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-300" aria-hidden="true">
                    <x-ui.icon name="currency" class="h-4 w-4" />
                </span>
                {{ __('Financeiro') }}
            </h3>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                @if ($paymentsCount > 0)
                    {{ trans_choice(':count pagamento|:count pagamentos', $paymentsCount, ['count' => $paymentsCount]) }}
                    · {{ __('Recebido') }} R$ {{ number_format($paymentStats['paid_total'] ?? 0, 2, ',', '.') }}
                    @if (($paymentStats['pending_total'] ?? 0) > 0)
                        · {{ __('Em aberto') }} R$ {{ number_format($paymentStats['pending_total'], 2, ',', '.') }}
                    @endif
                @else
                    {{ __('Ainda sem pagamentos registrados para este paciente.') }}
                @endif
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @can('create', \App\Models\Payment::class)
                <a
                    href="{{ route('payments.create', ['patient_id' => $patient->id]) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-500"
                >
                    <x-ui.icon name="plus" class="h-3.5 w-3.5" />
                    {{ __('Novo pagamento') }}
                </a>
            @endcan
            <a
                href="{{ route('patients.show', ['patient' => $patient, 'tab' => 'payments']) }}"
                class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-emerald-700 transition hover:border-emerald-200 hover:bg-emerald-50 dark:border-slate-600 dark:bg-slate-800 dark:text-emerald-300 dark:hover:bg-slate-700"
            >{{ __('Ver histórico') }} →</a>
        </div>
    </div>

    @if ($latestPayment)
        @php
            $badgeVariant = match ($latestPayment->status) {
                \App\Enums\PaymentStatus::Paid => 'success',
                \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::PendingConfirmation => 'warning',
                \App\Enums\PaymentStatus::Overdue => 'danger',
                \App\Enums\PaymentStatus::Cancelled => 'neutral',
                default => 'neutral',
            };
        @endphp
        <div class="p-5">
            <a
                href="{{ route('payments.show', $latestPayment) }}"
                class="group block rounded-xl border border-emerald-200/70 bg-emerald-50/40 p-4 transition hover:border-emerald-300 hover:bg-emerald-50 dark:border-emerald-800/50 dark:bg-emerald-950/25 dark:hover:border-emerald-700"
            >
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Último pagamento') }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <p class="text-lg font-bold tabular-nums text-slate-900 dark:text-white">
                        R$ {{ number_format((float) $latestPayment->amount, 2, ',', '.') }}
                    </p>
                    <x-ui.badge :variant="$badgeVariant">{{ $latestPayment->status?->label() ?? __('—') }}</x-ui.badge>
                </div>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                    {{ $latestPayment->created_at->format('d/m/Y H:i') }}
                    · {{ $latestPayment->payment_method?->label() ?? __('Não informado') }}
                    @if ($latestPayment->therapySession)
                        · {{ __('Sessão') }} {{ $latestPayment->therapySession->session_date->format('d/m/Y') }}
                    @endif
                </p>
                <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ __('Abrir pagamento') }}
                    <x-ui.icon name="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" />
                </span>
            </a>
        </div>
    @endif
</section>
