@props([
    'title',
    'message' => null,
    'dismissMs' => 5000,
])

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition
    @if ($dismissMs > 0) x-init="setTimeout(() => show = false, {{ (int) $dismissMs }})" @endif
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border border-emerald-200/90 bg-gradient-to-r from-emerald-50 to-teal-50 px-4 py-3 text-sm shadow-sm dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-teal-950/30']) }}
    role="status"
    aria-live="polite"
>
    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400" aria-hidden="true">
        <x-ui.icon name="check" class="h-4 w-4" />
    </span>
    <div class="min-w-0 flex-1">
        <p class="font-semibold text-emerald-900 dark:text-emerald-100">{{ $title }}</p>
        @if ($message)
            <p class="mt-0.5 text-emerald-800/90 dark:text-emerald-200/90">{{ $message }}</p>
        @endif
    </div>
    <button
        type="button"
        x-on:click="show = false"
        class="shrink-0 rounded-lg p-1 text-emerald-700/60 transition hover:bg-emerald-500/10 hover:text-emerald-800 dark:text-emerald-300/70 dark:hover:text-emerald-200"
        aria-label="{{ __('Fechar') }}"
    >
        <x-ui.icon name="x" class="h-4 w-4" />
    </button>
</div>
