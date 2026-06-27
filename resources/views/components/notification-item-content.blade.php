@props([
    'presented',
    'compact' => false,
    'tone' => 'system',
])

<div class="flex items-start gap-3">
    <x-notification-tone-icon :tone="$tone" :compact="$compact">
        <x-ui.icon :name="$presented['icon']" @class(['h-4 w-4' => ! $compact, 'h-3.5 w-3.5' => $compact]) />
    </x-notification-tone-icon>

    <div class="min-w-0 flex-1">
        <p @class([
            'font-medium leading-snug text-slate-800 dark:text-slate-100',
            'text-[13px] line-clamp-2' => $compact,
            'text-sm' => ! $compact,
            'font-semibold' => $presented['is_unread'] && ! $compact,
        ])>
            {{ $presented['title'] }}
        </p>

        @if (filled($presented['message']))
            <p @class([
                'mt-1 leading-relaxed text-slate-500 dark:text-slate-400',
                'line-clamp-2 text-xs' => $compact,
                'line-clamp-3 text-sm' => ! $compact,
            ])>
                {{ $presented['message'] }}
            </p>
        @endif

        @if ($presented['created_at'])
            <p class="mt-1.5 text-[11px] text-slate-400 dark:text-slate-500">
                {{ $presented['created_at']->diffForHumans() }}
            </p>
        @endif
    </div>

    @if ($presented['is_unread'])
        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500" aria-hidden="true"></span>
    @endif
</div>
