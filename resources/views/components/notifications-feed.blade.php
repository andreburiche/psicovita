@props([
    'notifications' => collect(),
    'maxHeight' => '32rem',
])

<ul
    @class([
        'mt-4 divide-y divide-slate-100 overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white dark:divide-slate-800 dark:border-slate-700 dark:bg-slate-900/50',
        '[scrollbar-color:theme(colors.slate.300)_transparent] [scrollbar-width:thin] dark:[scrollbar-color:theme(colors.slate.600)_transparent]',
        '[&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-slate-600',
    ])
    style="max-height: {{ $maxHeight }};"
>
    @forelse ($notifications as $notification)
        <x-notification-item :notification="$notification" />
    @empty
        <li class="px-6 py-12 text-center">
            <x-ui.icon name="bell" class="mx-auto mb-2 h-8 w-8 text-slate-300 dark:text-slate-600" />
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Sem notificações recentes.') }}</p>
        </li>
    @endforelse
</ul>
