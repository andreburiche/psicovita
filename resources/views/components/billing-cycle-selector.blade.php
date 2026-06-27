@props([
    'name' => 'billing_cycle',
    'selected' => null,
    'savingsPercent' => null,
])

@php
    $monthlyValue = \App\Enums\BillingCycle::Monthly->value;
    $yearlyValue = \App\Enums\BillingCycle::Yearly->value;
@endphp

<fieldset class="space-y-3">
    <legend class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Periodicidade') }}</legend>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="relative flex cursor-pointer rounded-2xl border border-slate-200 p-4 transition hover:border-violet-400 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50/80 dark:border-slate-600 dark:hover:border-violet-600 dark:has-[:checked]:border-violet-500 dark:has-[:checked]:bg-violet-950/30">
            <input
                type="radio"
                name="{{ $name }}"
                value="{{ $monthlyValue }}"
                class="mt-1 text-violet-600 focus:ring-violet-500"
                @checked($selected === null || $selected === $monthlyValue)
                required
            >
            <span class="ms-3">
                <span class="block text-sm font-semibold text-slate-900 dark:text-white">{{ __('Mensal') }}</span>
                <span class="mt-1 block text-xs text-slate-600 dark:text-slate-400">{{ __('Renovação a cada mês') }}</span>
            </span>
        </label>
        <label class="relative flex cursor-pointer rounded-2xl border border-slate-200 p-4 transition hover:border-violet-400 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50/80 dark:border-slate-600 dark:hover:border-violet-600 dark:has-[:checked]:border-violet-500 dark:has-[:checked]:bg-violet-950/30">
            <input
                type="radio"
                name="{{ $name }}"
                value="{{ $yearlyValue }}"
                class="mt-1 text-violet-600 focus:ring-violet-500"
                @checked($selected === $yearlyValue)
                required
            >
            <span class="ms-3">
                <span class="block text-sm font-semibold text-slate-900 dark:text-white">{{ __('Anual') }}</span>
                <span class="mt-1 block text-xs text-slate-600 dark:text-slate-400">
                    @if ($savingsPercent)
                        {{ __('Economize até :percent% pagando por ano', ['percent' => $savingsPercent]) }}
                    @else
                        {{ __('Cobrança única anual') }}
                    @endif
                </span>
            </span>
        </label>
    </div>
</fieldset>
