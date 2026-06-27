@props([
    'variant' => 'clinical',
])

@auth
    @php
        $user = auth()->user();
        $unreadCount = $user->unreadNotifications()->count();
        $recentNotifications = $user->notifications()->latest()->limit(12)->get();
        $notificationsIndexUrl = \App\Support\NotificationPresenter::indexUrl($user);
        $badgeLabel = $unreadCount > 9 ? '9+' : (string) $unreadCount;
        $totalRecent = $recentNotifications->count();
        $isPatient = $variant === 'patient';

        $linkAccent = $isPatient
            ? 'text-emerald-600 hover:text-emerald-500 dark:text-emerald-400'
            : 'text-violet-600 hover:text-violet-500 dark:text-violet-400';
    @endphp

    <div
        class="relative"
        x-data="{ open: false }"
        @click.outside="open = false"
        @keydown.escape.window="open = false"
    >
        <button
            type="button"
            class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-300/80 bg-white text-slate-500 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500/40 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-500 dark:hover:bg-slate-700 dark:hover:text-slate-200"
            @click="open = ! open"
            :aria-expanded="open"
            aria-haspopup="menu"
            aria-controls="notifications-menu"
            title="{{ __('Notificações') }}"
            aria-label="{{ $unreadCount > 0 ? __('Notificações (:count não lidas)', ['count' => $unreadCount]) : __('Notificações') }}"
        >
            <x-ui.icon name="bell" class="h-5 w-5 shrink-0" />

            @if ($unreadCount > 0)
                @if ($unreadCount === 1)
                    <span
                        class="absolute end-2 top-2 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-slate-800"
                        aria-hidden="true"
                    ></span>
                @else
                    <span class="absolute -end-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold leading-none text-white ring-2 ring-white dark:ring-slate-800">
                        {{ $badgeLabel }}
                    </span>
                @endif
            @endif
        </button>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-1"
            id="notifications-menu"
            role="menu"
            class="absolute end-0 z-50 mt-2 flex w-[26rem] max-w-[calc(100vw-2rem)] origin-top-right flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
            style="display: none; max-height: min(32rem, calc(100dvh - 5rem));"
        >
            <div class="flex shrink-0 flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-slate-100 px-5 py-3.5 dark:border-slate-800">
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                    {{ __('Notificações') }}
                    @if ($unreadCount > 0)
                        <span class="ms-1.5 text-xs font-normal text-slate-500 dark:text-slate-400">
                            · {{ trans_choice(':count nova|:count novas', $unreadCount, ['count' => $unreadCount]) }}
                        </span>
                    @endif
                </p>

                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                        @csrf
                        <button type="submit" class="text-xs font-medium {{ $linkAccent }}">
                            {{ __('Marcar lidas') }}
                        </button>
                    </form>
                @endif
            </div>

            <ul
                class="min-h-0 flex-1 overflow-y-auto overscroll-contain [scrollbar-color:theme(colors.slate.300)_transparent] [scrollbar-width:thin] dark:[scrollbar-color:theme(colors.slate.600)_transparent] [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-slate-600"
                role="none"
            >
                @forelse ($recentNotifications as $notification)
                    <x-notification-item :notification="$notification" compact />
                @empty
                    <li class="px-5 py-12 text-center">
                        <p class="text-sm text-slate-400 dark:text-slate-500">{{ __('Nenhuma notificação') }}</p>
                    </li>
                @endforelse
            </ul>

            @if ($totalRecent > 0)
                <div class="shrink-0 border-t border-slate-100 bg-white px-5 py-3 dark:border-slate-800 dark:bg-slate-900">
                    <a
                        href="{{ $notificationsIndexUrl }}"
                        class="block text-center text-xs font-medium {{ $linkAccent }}"
                        @click="open = false"
                    >
                        {{ __('Ver todas') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
@endauth
