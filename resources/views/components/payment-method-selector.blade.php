@props([
    'name' => 'payment_method',
    'selected' => null,
])

@php
    $pixValue = \App\Enums\PaymentMethod::Pix->value;
    $cardValue = \App\Enums\PaymentMethod::Card->value;
@endphp

<fieldset class="space-y-3">
    <legend class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Como deseja pagar?') }}</legend>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="relative flex cursor-pointer rounded-2xl border border-slate-200 p-4 transition hover:border-emerald-400 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/80 dark:border-slate-600 dark:hover:border-emerald-600 dark:has-[:checked]:border-emerald-500 dark:has-[:checked]:bg-emerald-950/30">
            <input
                type="radio"
                name="{{ $name }}"
                value="{{ $pixValue }}"
                class="mt-1 text-emerald-600 focus:ring-emerald-500"
                @checked($selected === $pixValue)
                required
            >
            <span class="ms-3">
                <span class="block text-sm font-semibold text-slate-900 dark:text-white">PIX</span>
                <span class="mt-1 block text-xs text-slate-600 dark:text-slate-400">{{ __('QR Code instantâneo') }}</span>
            </span>
        </label>
        <label class="relative flex cursor-pointer rounded-2xl border border-slate-200 p-4 transition hover:border-indigo-400 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/80 dark:border-slate-600 dark:hover:border-indigo-600 dark:has-[:checked]:border-indigo-500 dark:has-[:checked]:bg-indigo-950/30">
            <input
                type="radio"
                name="{{ $name }}"
                value="{{ $cardValue }}"
                class="mt-1 text-indigo-600 focus:ring-indigo-500"
                @checked($selected === $cardValue)
                required
            >
            <span class="ms-3">
                <span class="block text-sm font-semibold text-slate-900 dark:text-white">{{ __('Cartão') }}</span>
                <span class="mt-1 block text-xs text-slate-600 dark:text-slate-400">{{ __('Checkout seguro no gateway') }}</span>
            </span>
        </label>
    </div>
</fieldset>
