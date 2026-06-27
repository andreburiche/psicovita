@props([
    'label' => __('Resultado'),
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-600 dark:bg-slate-800/50']) }}>
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</p>
    <p class="mt-2 rounded-lg border border-amber-200/80 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-100">
        {{ __('Conteúdo gerado por IA e deve ser revisado pelo profissional.') }}
    </p>
    <div class="mt-3 text-sm leading-relaxed text-slate-800 dark:text-slate-100">
        {{ $slot }}
    </div>
</div>
