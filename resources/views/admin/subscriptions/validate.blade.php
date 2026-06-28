<x-app-layout>
    <x-slot name="header">{{ __('Validar pagamento manual') }}</x-slot>

    <div class="mx-auto max-w-2xl space-y-6">
        <x-page-hero
            :title="__('Confirmar assinatura manualmente')"
            :subtitle="__('Após o profissional efectuar o pagamento, confirme aqui para reactivar o plano. Pagamento + validação administrativa são obrigatórios.')"
            icon="banknote"
            iconTone="emerald"
        />

        @if ($errors->has('manual'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-100">
                {{ $errors->first('manual') }}
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $subscription->user?->name }}</p>
            <p class="text-xs text-slate-500">{{ $subscription->user?->email }}</p>
            <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">{{ __('Plano actual') }}</dt>
                    <dd class="font-medium text-slate-900 dark:text-white">{{ $subscription->plan?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Status actual') }}</dt>
                    <dd>
                        <span @class(['inline-flex rounded-full px-2 py-0.5 text-xs font-semibold', $subscription->status->badgeClass()])>
                            {{ $subscription->status->label() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Pagamento confirmado') }}</dt>
                    <dd class="font-medium text-slate-900 dark:text-white">
                        {{ $subscription->hasPaymentConfirmation() ? __('Sim') : __('Não — aguardando pagamento do profissional') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Validade actual') }}</dt>
                    <dd class="font-medium text-slate-900 dark:text-white">{{ $subscription->expirationDate()?->format('d/m/Y') ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <form method="POST" action="{{ route('admin.subscriptions.update', $subscription) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
            @csrf
            @method('PATCH')

            <div>
                <x-input-label for="subscription_plan_id" :value="__('Plano a activar')" />
                <select id="subscription_plan_id" name="subscription_plan_id" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800">
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(old('subscription_plan_id', $subscription->subscription_plan_id) == $plan->id)>
                            {{ $plan->name }} — R$ {{ number_format($plan->price_cents / 100, 2, ',', '.') }}/{{ __('mês') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="billing_cycle" :value="__('Período de validade')" />
                <select id="billing_cycle" name="billing_cycle" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800">
                    @foreach ($billingCycles as $cycle)
                        <option value="{{ $cycle->value }}" @selected(old('billing_cycle', $subscription->gateway_meta['billing_cycle'] ?? 'monthly') === $cycle->value)>
                            {{ $cycle->label() }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">{{ __('Se não indicar data personalizada, soma 1 mês ou 1 ano a partir de hoje (ou estende a validade actual se ainda estiver activa).') }}</p>
            </div>

            <div>
                <x-input-label for="valid_until" :value="__('Validade personalizada (opcional)')" />
                <input type="date" id="valid_until" name="valid_until" value="{{ old('valid_until') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800" />
            </div>

            <div>
                <x-input-label for="note" :value="__('Observação interna (opcional)')" />
                <textarea id="note" name="note" rows="3" maxlength="500" placeholder="{{ __('Ex.: PIX recebido em 04/06/2026, comprovante guardado.') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800">{{ old('note') }}</textarea>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4 dark:border-slate-700">
                <a href="{{ route('admin.subscriptions.index') }}" class="text-sm font-semibold text-slate-600 hover:underline dark:text-slate-300">{{ __('Voltar') }}</a>
                <x-primary-button>{{ __('Confirmar pagamento manual') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
