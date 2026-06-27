<x-patient-layout>
    <x-slot name="header">{{ __('Detalhe do pagamento') }}</x-slot>

    <x-patient-portal-shell>
    @php
        $method = $payment->payment_method;
        $isPix = $method === \App\Enums\PaymentMethod::Pix;
        $isCard = $method === \App\Enums\PaymentMethod::Card;
        $pix = $isPix ? ($payment->gateway_meta['pix'] ?? null) : null;
        $invoiceUrl = $payment->gateway_meta['invoice_url'] ?? null;
        $isStub = (bool) ($payment->gateway_meta['stub'] ?? false);
    @endphp

    <x-patient-portal-breadcrumb :items="[
        ['label' => __('Início'), 'href' => route('patient.home')],
        ['label' => __('Pagamentos'), 'href' => route('patient.payments.index')],
        ['label' => 'R$ '.number_format((float) $payment->amount, 2, ',', '.')],
    ]" />

    <x-patient-portal-hero
        :title="'R$ '.number_format((float) $payment->amount, 2, ',', '.')"
        :subtitle="$payment->therapySession
            ? __('Sessão de :date', ['date' => $payment->therapySession->session_date->format('d/m/Y')])
            : __('Detalhe da cobrança')"
        icon="currency"
    >
        <x-slot name="actions">
            <x-ui.badge :variant="match ($payment->status) {
                \App\Enums\PaymentStatus::Paid => 'success',
                \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::Overdue => 'warning',
                default => 'neutral',
            }" class="!text-sm">{{ $payment->status->label() }}</x-ui.badge>
        </x-slot>
    </x-patient-portal-hero>

    <div class="grid gap-4 sm:grid-cols-2">
        @if ($method)
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/80">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Forma de pagamento') }}</p>
                <p class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $method->label() }}</p>
            </div>
        @endif
        @if ($payment->paid_at)
            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/80 p-4 dark:border-emerald-800/40 dark:bg-emerald-950/30">
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">{{ __('Pago em') }}</p>
                <p class="mt-1 font-semibold text-emerald-900 dark:text-emerald-100">{{ $payment->paid_at->format('d/m/Y H:i') }}</p>
            </div>
        @endif
    </div>

    @if ($payment->status === \App\Enums\PaymentStatus::Paid)
        <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/80 p-5 dark:border-emerald-800/40 dark:bg-emerald-950/30">
            <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">{{ __('Pagamento confirmado') }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Obrigado. A sua sessão está liquidada na plataforma.') }}</p>
        </div>
    @elseif ($needsMethodChoice ?? false)
        @can('pay', $payment)
            <form method="POST" action="{{ route('patient.payments.pay', $payment) }}" class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                @csrf
                <x-payment-method-selector :selected="old('payment_method')" />
                <button
                    type="submit"
                    class="mt-5 inline-flex items-center rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:from-emerald-500 hover:to-teal-500"
                >
                    {{ __('Continuar para pagamento') }}
                </button>
            </form>
        @endcan
    @elseif ($isPix && is_array($pix))
        <x-pix-checkout-panel :pix="$pix" :stub="$isStub" />
    @elseif ($isCard && filled($invoiceUrl))
        <x-card-checkout-panel :invoice-url="$invoiceUrl" :stub="$isStub" />
    @elseif ($isCard)
        <div class="rounded-2xl border border-indigo-200/80 bg-indigo-50/80 p-5 dark:border-indigo-800/40 dark:bg-indigo-950/30">
            <p class="text-sm text-slate-700 dark:text-slate-300">{{ __('Clique em «Gerar link de pagamento» para abrir o checkout com cartão.') }}</p>
        </div>
    @endif

    <div class="flex flex-wrap gap-3 border-t border-slate-200/80 pt-4 dark:border-slate-700">
        <a
            href="{{ route('patient.payments.index') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
        >
            <x-ui.icon name="arrow-left" class="h-4 w-4" />
            {{ __('Voltar à lista') }}
        </a>
        @can('pay', $payment)
            @unless ($needsMethodChoice ?? false)
                <form method="POST" action="{{ route('patient.payments.pay', $payment) }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-2xl bg-gradient-to-r {{ $isCard ? 'from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500' : 'from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500' }} px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition"
                    >
                        @if ($isCard)
                            {{ filled($invoiceUrl) ? __('Gerar novo link de cartão') : __('Gerar link de pagamento') }}
                        @else
                            {{ is_array($pix) ? __('Gerar novo QR PIX') : __('Pagar com PIX') }}
                        @endif
                    </button>
                </form>
            @endunless
        @endcan
    </div>
    </x-patient-portal-shell>
</x-patient-layout>
