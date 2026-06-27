<x-app-layout>
    <x-slot name="header">{{ __('Assinatura da plataforma') }}</x-slot>

    @php
        $checkoutMeta = $subscription?->gateway_meta ?? [];
        $checkoutMethod = isset($checkoutMeta['payment_method'])
            ? \App\Enums\PaymentMethod::from((string) $checkoutMeta['payment_method'])
            : null;
        $isPixCheckout = $checkoutMethod === \App\Enums\PaymentMethod::Pix;
        $isCardCheckout = $checkoutMethod === \App\Enums\PaymentMethod::Card;
        $pix = $isPixCheckout ? ($checkoutMeta['pix'] ?? null) : null;
        $invoiceUrl = $checkoutMeta['invoice_url'] ?? null;
        $isStub = (bool) ($checkoutMeta['stub'] ?? false);
        $awaitingPayment = $subscription?->status === \App\Enums\SubscriptionStatus::PastDue;
        $showPixCheckout = $showPixCheckout ?? false;
        $pixPending = $awaitingPayment && $isPixCheckout && ! $showPixCheckout;
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-page-hero
                :title="__('Planos PsiConecta')"
                :subtitle="__('Escolha o plano ideal e pague de forma segura via gateway.')"
                icon="credit-card"
            />

            @if (($patientQuota['at_limit'] ?? false) || ($patientQuota['near_limit'] ?? false))
                <x-patient-quota-alert :quota="$patientQuota" />
            @endif

            @if ($subscription && ($awaitingPayment || $showPixCheckout || ($isActive && filled($checkoutMeta))))
                <section id="subscription-status" class="rounded-2xl border border-violet-200/80 bg-white p-6 shadow-lg dark:border-slate-700 dark:bg-slate-900/90">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Estado da assinatura') }}</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        {{ __('Plano') }}: <span class="font-semibold">{{ $subscription->plan?->name }}</span>
                        · {{ $subscription->status->label() }}
                    </p>

                    @if ($showPixCheckout && is_array($pix))
                        <div class="mt-6" id="pix-checkout">
                            <x-pix-checkout-panel :pix="$pix" :stub="$isStub" />
                        </div>
                    @elseif ($pixPending)
                        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
                            {{ __('A gerar o QR Code PIX. Aguarde alguns segundos e recarregue a página.') }}
                        </p>
                    @elseif ($awaitingPayment && $isCardCheckout && filled($invoiceUrl))
                        <div class="mt-6">
                            <x-card-checkout-panel :invoice-url="$invoiceUrl" :stub="$isStub" />
                        </div>
                    @elseif ($isActive && $subscription->ends_at)
                        <p class="mt-4 text-sm text-emerald-700 dark:text-emerald-300">
                            {{ __('Válida até') }} {{ $subscription->ends_at->format('d/m/Y') }}
                        </p>
                    @endif

                    @if ($subscription->cancelled_at)
                        <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">
                            {{ __('Renovação cancelada em') }} {{ $subscription->cancelled_at->format('d/m/Y') }}.
                            @if ($subscription->ends_at)
                                {{ __('Acesso até') }} {{ $subscription->ends_at->format('d/m/Y') }}.
                            @endif
                        </p>
                    @endif

                    @if ($canCancel ?? false)
                        <form method="POST" action="{{ route('subscription.checkout.cancel') }}" class="mt-6" onsubmit="return confirm(@js(__('Cancelar a renovação automática? O acesso mantém-se até ao fim do período pago.')));">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-xl border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-300 dark:hover:bg-rose-950/40"
                            >
                                {{ __('Cancelar renovação') }}
                            </button>
                        </form>
                    @endif
                </section>
            @endif

            <form method="POST" action="{{ route('subscription.checkout.store') }}" class="space-y-6">
                @csrf

                @error('checkout')
                    <x-flash-alert type="error" :message="$message" />
                @enderror

                <div class="grid gap-4 lg:grid-cols-3">
                    @foreach ($plans as $plan)
                        <label class="flex cursor-pointer flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-violet-400 has-[:checked]:border-violet-500 has-[:checked]:ring-2 has-[:checked]:ring-violet-200 dark:border-slate-700 dark:bg-slate-900/80 dark:has-[:checked]:ring-violet-900/50">
                            <input
                                type="radio"
                                name="subscription_plan_id"
                                value="{{ $plan->id }}"
                                class="text-violet-600 focus:ring-violet-500"
                                @checked(old('subscription_plan_id', $subscription?->subscription_plan_id) == $plan->id)
                                required
                            >
                            <span class="mt-3 text-lg font-bold text-slate-900 dark:text-white">{{ $plan->name }}</span>
                            <span class="mt-1 text-2xl font-extrabold text-violet-700 dark:text-violet-300">{{ $plan->formattedPrice() }}</span>
                            <span class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('por mês') }} · {{ $plan->formattedAnnualPrice() }}/{{ __('ano') }}</span>
                            @if ($plan->annualSavingsPercent() > 0)
                                <span class="mt-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                    {{ __('Anual: economize :percent%', ['percent' => $plan->annualSavingsPercent()]) }}
                                </span>
                            @endif
                            <ul class="mt-4 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                                @if ($plan->hasFeature('use_ai'))
                                    <li>✓ {{ __('IA clínica') }}</li>
                                @endif
                                @if ($plan->hasFeature('multi_user'))
                                    <li>✓ {{ __('Multi-utilizador') }}</li>
                                @endif
                                <li>✓ {{ __('Pacientes, sessões e prontuários') }}</li>
                            </ul>
                        </label>
                    @endforeach
                </div>

                <section class="rounded-2xl border border-slate-200/90 bg-white p-5 dark:border-slate-700 dark:bg-slate-900/80">
                    <x-billing-cycle-selector
                        name="billing_cycle"
                        :selected="old('billing_cycle', $checkoutMeta['billing_cycle'] ?? null)"
                        :savings-percent="$maxAnnualSavingsPercent ?? null"
                    />
                </section>

                <section class="rounded-2xl border border-slate-200/90 bg-white p-5 dark:border-slate-700 dark:bg-slate-900/80">
                    <x-payment-method-selector
                        name="payment_method"
                        :selected="old('payment_method', $checkoutMeta['payment_method'] ?? null)"
                    />
                </section>

                <button
                    type="submit"
                    class="inline-flex items-center rounded-2xl bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:from-violet-500 hover:to-indigo-500"
                >
                    {{ ($isActive ?? false) ? __('Alterar ou renovar plano') : __('Subscrever plano') }}
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
