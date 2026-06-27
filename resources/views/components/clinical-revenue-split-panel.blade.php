@props([
    'gross' => '0,00',
    'professional' => '0,00',
    'platformFee' => '0,00',
])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50/80 to-teal-50/60 p-5 shadow-sm dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-teal-950/20']) }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wide text-emerald-900 dark:text-emerald-200">{{ __('Receita clínica (mês)') }}</h2>
            <p class="mt-1 text-xs text-emerald-800/80 dark:text-emerald-300/80">{{ __('Pagamentos liquidados com repasse estimado da plataforma.') }}</p>
        </div>
        <a
            href="{{ route('payments.index') }}"
            class="inline-flex shrink-0 items-center rounded-xl border border-emerald-300/80 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-50 dark:border-emerald-800 dark:bg-slate-900/60 dark:text-emerald-200 dark:hover:bg-emerald-950/40"
        >
            {{ __('Ver financeiro') }}
        </a>
    </div>

    <dl class="mt-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl bg-white/80 px-4 py-3 dark:bg-slate-900/50">
            <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Bruto recebido') }}</dt>
            <dd class="mt-1 text-xl font-bold tabular-nums text-slate-900 dark:text-white">R$ {{ $gross }}</dd>
        </div>
        <div class="rounded-xl bg-white/80 px-4 py-3 ring-1 ring-emerald-200/60 dark:bg-slate-900/50 dark:ring-emerald-900/40">
            <dt class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">{{ __('Seu repasse') }}</dt>
            <dd class="mt-1 text-xl font-bold tabular-nums text-emerald-900 dark:text-emerald-100">R$ {{ $professional }}</dd>
        </div>
        <div class="rounded-xl bg-white/80 px-4 py-3 dark:bg-slate-900/50">
            <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Comissão plataforma') }}</dt>
            <dd class="mt-1 text-xl font-bold tabular-nums text-slate-700 dark:text-slate-300">R$ {{ $platformFee }}</dd>
        </div>
    </dl>
</section>
