@props([
    'count' => 0,
    'total' => '0,00',
])

@if ($count > 0)
    <div {{ $attributes->merge(['class' => 'rounded-2xl border border-amber-200/80 bg-gradient-to-r from-amber-50 to-orange-50 px-4 py-3 text-sm shadow-sm dark:border-amber-800/50 dark:from-amber-950/40 dark:to-orange-950/30']) }} role="status">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="font-medium text-amber-950 dark:text-amber-100">
                {{ trans_choice(':count cobrança pendente no portal|:count cobranças pendentes no portal', $count, ['count' => $count]) }}
                — <span class="font-bold">R$ {{ $total }}</span>
            </p>
            <a
                href="{{ route('payments.index', ['status' => 'pending']) }}"
                class="inline-flex shrink-0 items-center rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-amber-500"
            >
                {{ __('Ver financeiro') }}
            </a>
        </div>
    </div>
@endif
