@php
    $subscription = $subscriptionSummary['subscription'] ?? null;
    $plan = $subscription?->plan;
    $isActive = $subscriptionSummary['is_active'] ?? false;
    $expiresAt = $subscriptionSummary['expires_at'] ?? null;
    $billingCycle = \App\Enums\BillingCycle::tryFrom((string) ($subscription?->gateway_meta['billing_cycle'] ?? ''));
@endphp
<section class="rounded-2xl border border-violet-200/80 bg-white p-6 shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/60 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-violet-900/30">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Assinatura da plataforma') }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                {{ __('Plano, validade e estado da sua subscrição PsiConecta.') }}
            </p>
        </div>
        @if ($isActive)
            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                {{ __('Activa') }}
            </span>
        @else
            <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                {{ __('Inactiva') }}
            </span>
        @endif
    </div>

    <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Plano') }}</dt>
            <dd class="mt-1 text-base font-semibold text-slate-900 dark:text-white">
                {{ $plan?->name ?? __('Sem plano') }}
            </dd>
        </div>
        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Estado') }}</dt>
            <dd class="mt-1 text-base font-semibold text-slate-900 dark:text-white">
                {{ $subscription?->status?->label() ?? __('—') }}
            </dd>
        </div>
        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Periodicidade') }}</dt>
            <dd class="mt-1 text-base font-semibold text-slate-900 dark:text-white">
                @if ($billingCycle)
                    {{ $billingCycle->label() }}
                @else
                    {{ __('—') }}
                @endif
            </dd>
        </div>
        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Validade') }}</dt>
            <dd class="mt-1 text-base font-semibold text-slate-900 dark:text-white">
                @if ($expiresAt)
                    {{ $expiresAt->format('d/m/Y') }}
                @else
                    {{ __('Sem data de término') }}
                @endif
            </dd>
        </div>
    </dl>

    @if (! $isActive)
        <p class="mt-4 text-sm text-rose-700 dark:text-rose-300">
            {{ __('Pode consultar os dados existentes, mas criar pacientes, sessões, prontuários e utilizar a IA está bloqueado até renovar.') }}
        </p>
    @endif

    <div class="mt-5">
        <a
            href="{{ route('subscription.checkout') }}"
            class="inline-flex items-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-violet-500 hover:to-indigo-500"
        >
            {{ $isActive ? __('Gerir ou alterar plano') : __('Subscrever ou renovar') }}
        </a>
    </div>
</section>
