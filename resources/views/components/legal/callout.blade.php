@props([
    'variant' => 'info',
    'title' => null,
])

@php
    $styles = match ($variant) {
        'contact' => [
            'wrap' => 'border-violet-200/80 bg-gradient-to-br from-violet-50 to-indigo-50/80 dark:border-violet-800/50 dark:from-violet-950/40 dark:to-indigo-950/30',
            'icon' => 'mail',
            'iconWrap' => 'bg-violet-600 text-white',
        ],
        'warning' => [
            'wrap' => 'border-amber-200/80 bg-amber-50/90 dark:border-amber-800/50 dark:bg-amber-950/30',
            'icon' => 'alert-triangle',
            'iconWrap' => 'bg-amber-500 text-white',
        ],
        'shield' => [
            'wrap' => 'border-emerald-200/80 bg-emerald-50/90 dark:border-emerald-800/50 dark:bg-emerald-950/30',
            'icon' => 'shield-check',
            'iconWrap' => 'bg-emerald-600 text-white',
        ],
        default => [
            'wrap' => 'border-slate-200/80 bg-slate-50/90 dark:border-slate-700 dark:bg-slate-800/50',
            'icon' => 'info',
            'iconWrap' => 'bg-slate-600 text-white dark:bg-slate-500',
        ],
    };
@endphp

<aside {{ $attributes->merge(['class' => "flex gap-3 rounded-2xl border p-4 sm:p-5 {$styles['wrap']}"]) }} role="note">
    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-sm {{ $styles['iconWrap'] }}" aria-hidden="true">
        <x-ui.icon :name="$styles['icon']" class="h-5 w-5" />
    </span>
    <div class="min-w-0 flex-1 text-sm leading-relaxed text-slate-700 dark:text-slate-200">
        @if ($title)
            <p class="font-bold text-slate-900 dark:text-white">{{ $title }}</p>
        @endif
        <div @class(['space-y-2', 'mt-1' => (bool) $title])>
            {{ $slot }}
        </div>
    </div>
</aside>
