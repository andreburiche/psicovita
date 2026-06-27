@props([
    'invoiceUrl' => null,
    'stub' => false,
])

@if (filled($invoiceUrl))
    <section class="rounded-2xl border border-indigo-200/80 bg-white p-5 shadow-sm dark:border-indigo-800/40 dark:bg-slate-900/80">
        <h2 class="text-sm font-bold uppercase tracking-wider text-indigo-800 dark:text-indigo-300">{{ __('Pagar com cartão') }}</h2>
        @if ($stub)
            <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">{{ __('Modo demonstração — configure ASAAS_ENABLED=true para cobranças reais.') }}</p>
        @endif
        <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">
            {{ __('Será redirecionado para o ambiente seguro do gateway para concluir o pagamento com cartão de crédito.') }}
        </p>
        <a
            href="{{ $invoiceUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-4 inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-indigo-500 hover:to-violet-500"
        >
            {{ __('Abrir página de pagamento') }}
        </a>
    </section>
@endif
