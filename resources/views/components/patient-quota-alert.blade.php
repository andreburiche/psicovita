@props([
    'quota' => [],
])

@php
    $limited = (bool) ($quota['limited'] ?? false);
    $count = (int) ($quota['count'] ?? 0);
    $limit = isset($quota['limit']) ? (int) $quota['limit'] : null;
    $remaining = isset($quota['remaining']) ? (int) $quota['remaining'] : null;
    $atLimit = (bool) ($quota['at_limit'] ?? false);
    $nearLimit = (bool) ($quota['near_limit'] ?? false);
@endphp

@if ($limited && $limit !== null)
    @php
        $tone = $atLimit ? 'rose' : ($nearLimit ? 'amber' : 'violet');
        $borderClass = match ($tone) {
            'rose' => 'border-rose-200/80 bg-gradient-to-r from-rose-50 to-orange-50 dark:border-rose-800/50 dark:from-rose-950/40 dark:to-orange-950/30',
            'amber' => 'border-amber-200/80 bg-gradient-to-r from-amber-50 to-orange-50 dark:border-amber-800/50 dark:from-amber-950/40 dark:to-orange-950/30',
            default => 'border-violet-200/80 bg-gradient-to-r from-violet-50/80 to-indigo-50/60 dark:border-violet-900/40 dark:from-violet-950/30 dark:to-indigo-950/20',
        };
        $textClass = match ($tone) {
            'rose' => 'text-rose-950 dark:text-rose-100',
            'amber' => 'text-amber-950 dark:text-amber-100',
            default => 'text-violet-950 dark:text-violet-100',
        };
        $buttonClass = match ($tone) {
            'rose' => 'bg-rose-600 hover:bg-rose-500',
            'amber' => 'bg-amber-600 hover:bg-amber-500',
            default => 'bg-violet-600 hover:bg-violet-500',
        };
    @endphp

    <div {{ $attributes->merge(['class' => "rounded-2xl border px-4 py-3 text-sm shadow-sm {$borderClass}"]) }} role="status">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="font-medium {{ $textClass }}">
                @if ($atLimit)
                    {{ __('Limite do plano atingido: :count de :limit pacientes.', ['count' => $count, 'limit' => $limit]) }}
                    {{ __('Actualize para Premium ou Clínica para adicionar mais.') }}
                @elseif ($nearLimit)
                    {{ __('Atenção: :count de :limit pacientes (:remaining restante(s)).', ['count' => $count, 'limit' => $limit, 'remaining' => $remaining]) }}
                @else
                    {{ __('Plano actual: :count de :limit pacientes.', ['count' => $count, 'limit' => $limit]) }}
                @endif
            </p>
            @if ($atLimit || $nearLimit)
                <a
                    href="{{ route('subscription.checkout') }}"
                    class="inline-flex shrink-0 items-center rounded-xl px-4 py-2 text-xs font-bold text-white transition {{ $buttonClass }}"
                >
                    {{ __('Ver planos') }}
                </a>
            @endif
        </div>
    </div>
@endif
