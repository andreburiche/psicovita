@props([
    'banner' => [],
])

@php
    $level = $banner['level'] ?? 'none';
    $message = $banner['message'] ?? null;
    $showRenewCta = $level !== 'none' && filled($message) && auth()->user()?->isProfessional();
    $alertType = $level === 'danger' ? 'error' : 'warning';
@endphp

@if ($showRenewCta)
    <div {{ $attributes->merge(['class' => 'mb-6']) }} x-data="{ show: true }" x-show="show" x-transition.opacity role="alert">
        <div @class([
            'flex flex-col gap-3 rounded-2xl border px-4 py-3 text-sm shadow-sm sm:flex-row sm:items-center sm:justify-between',
            'border-rose-200/80 bg-gradient-to-r from-rose-50 to-orange-50 text-rose-950 dark:border-rose-800/50 dark:from-rose-950/50 dark:to-orange-950/30 dark:text-rose-100' => $level === 'danger',
            'border-amber-200/80 bg-gradient-to-r from-amber-50 to-orange-50 text-amber-950 dark:border-amber-800/50 dark:from-amber-950/50 dark:to-orange-950/30 dark:text-amber-100' => $level === 'warning',
        ])>
            <div class="flex min-w-0 flex-1 items-start gap-3">
                <span @class([
                    'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl',
                    'bg-rose-500/15 text-rose-600 dark:text-rose-400' => $level === 'danger',
                    'bg-amber-500/15 text-amber-600 dark:text-amber-400' => $level === 'warning',
                ]) aria-hidden="true">
                    <x-ui.icon :name="$level === 'danger' ? 'alert-circle' : 'alert-triangle'" class="h-4 w-4" />
                </span>
                <p class="min-w-0 flex-1 pt-0.5 font-medium">{{ $message }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <a
                    href="{{ route('subscription.checkout') }}"
                    class="inline-flex items-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-md transition hover:from-violet-500 hover:to-indigo-500 sm:text-sm"
                >
                    {{ $banner['cta_label'] ?? __('Renovar assinatura') }}
                </a>
                <button
                    type="button"
                    x-on:click="show = false"
                    class="rounded-lg p-1 text-current/60 transition hover:bg-black/5 hover:text-current dark:hover:bg-white/10"
                    aria-label="{{ __('Fechar') }}"
                >
                    <x-ui.icon name="x" class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
@endif

@if (session('subscription_blocked'))
    <x-flash-alert type="error" :message="session('subscription_blocked')" :dismissible="true" {{ $attributes }} />
@endif
