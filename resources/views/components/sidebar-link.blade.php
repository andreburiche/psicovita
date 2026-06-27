@props([
    'href',
    'active' => false,
    'label',
    'badge' => null,
])

@php
    $classes = 'group/side flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-200 ';
    $classes .= $active
        ? 'bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-lg shadow-violet-900/40 ring-1 ring-white/25'
        : 'text-slate-200 hover:bg-white/10 hover:text-white';
    $titleLabel = filled($badge) ? $label.' ('.$badge.')' : $label;
    $badgeIsFull = false;

    if (filled($badge) && str_contains((string) $badge, '/')) {
        [$badgeCount, $badgeLimit] = explode('/', (string) $badge, 2);
        $badgeIsFull = (int) $badgeCount >= (int) $badgeLimit;
    }
@endphp

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->merge(['class' => $classes]) }}
    x-bind:class="sidebarCollapsed && isDesktop ? 'lg:justify-center lg:gap-0 lg:px-2' : ''"
    x-bind:title="sidebarCollapsed && isDesktop ? @js($titleLabel) : ''"
>
    <span @class([
        'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg [&_svg]:h-5 [&_svg]:w-5',
        'bg-white/20 text-white ring-1 ring-white/30' => $active,
        'bg-white/5 text-violet-100 ring-1 ring-white/10 group-hover/side:bg-violet-500/30 group-hover/side:text-white group-hover/side:ring-violet-300/30' => ! $active,
    ])
        x-bind:class="sidebarCollapsed && isDesktop ? 'lg:mx-auto' : ''"
    >
        {{ $icon }}
    </span>
    <span
        class="flex min-w-0 flex-1 items-center gap-2"
        x-show="navLabelsVisible()"
        x-transition.opacity
    >
        <span class="min-w-0 truncate">{{ $label }}</span>
        @if (filled($badge))
            <span @class([
                'ms-auto shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums ring-1 ring-inset',
                'bg-white/20 text-white ring-white/30' => $active && ! $badgeIsFull,
                'bg-rose-500/20 text-rose-100 ring-rose-300/40' => $active && $badgeIsFull,
                'bg-violet-500/20 text-violet-100 ring-violet-300/30' => ! $active && ! $badgeIsFull,
                'bg-rose-500/25 text-rose-100 ring-rose-300/40' => ! $active && $badgeIsFull,
            ])>{{ $badge }}</span>
        @endif
    </span>
</a>
