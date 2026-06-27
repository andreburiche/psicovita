@props([
    'subscription',
])

@php
    $needsValidation = in_array($subscription->status->value, ['trialing', 'past_due', 'expired'], true);
    $actionLabel = $needsValidation ? __('Validar pagamento') : __('Renovar / ajustar');
    $actionIcon = $needsValidation ? 'check-badge' : 'pencil';
    $actionButtonClass = $needsValidation
        ? 'inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-900/50'
        : 'inline-flex items-center gap-2 rounded-xl border border-violet-200 bg-white px-3 py-2 text-xs font-semibold text-violet-700 shadow-sm transition hover:border-violet-300 hover:bg-violet-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-500 dark:border-violet-800/60 dark:bg-slate-900 dark:text-violet-300 dark:hover:bg-violet-950/40';
    $actionIconClass = $needsValidation
        ? 'flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300'
        : 'flex h-7 w-7 items-center justify-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-900/60 dark:text-violet-300';
@endphp

<a href="{{ route('admin.subscriptions.validate', $subscription) }}" class="{{ $actionButtonClass }}">
    <span class="{{ $actionIconClass }}">
        <x-ui.icon :name="$actionIcon" class="h-4 w-4" />
    </span>
    <span class="whitespace-nowrap">{{ $actionLabel }}</span>
</a>
