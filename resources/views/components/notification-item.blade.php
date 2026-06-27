@props([
    'notification',
    'compact' => false,
])

@php
    $presented = \App\Support\NotificationPresenter::present($notification);
@endphp

<li @class(['border-b border-slate-100 last:border-b-0 dark:border-slate-800' => $compact])>
    @if (filled($presented['action_url']) && auth()->check())
        <a
            href="{{ route('notifications.open', $notification->id) }}"
            @class([
                'group block transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50',
                'px-5 py-3.5' => $compact,
                'px-5 py-4' => ! $compact,
                'bg-slate-50/60 dark:bg-slate-800/25' => $presented['is_unread'] && $compact,
            ])
        >
            <x-notification-item-content :presented="$presented" :compact="$compact" :tone="$presented['tone']" />
        </a>
    @else
        <div @class([
            'block',
            'px-5 py-3.5' => $compact,
            'px-5 py-4' => ! $compact,
            'bg-slate-50/60 dark:bg-slate-800/25' => $presented['is_unread'],
        ])>
            <x-notification-item-content :presented="$presented" :compact="$compact" :tone="$presented['tone']" />
        </div>
    @endif
</li>
