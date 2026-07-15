@props([
    'user',
    'size' => 'md',
    'showRing' => true,
    /** Sobrescreve estilo guardado (pré-visualização no editor). */
    'shape' => null,
    'ring' => null,
    'filter' => null,
])

@php
    $resolved = $user->resolvedAvatarStyle();
    $shape = $shape ?? $resolved['shape'];
    $ring = $ring ?? $resolved['ring'];
    $filter = $filter ?? $resolved['filter'];

    $sizeClasses = match ($size) {
        'xs' => 'h-8 w-8 text-xs',
        'sm' => 'h-10 w-10 text-sm',
        'lg' => 'h-24 w-24 text-2xl',
        'xl' => 'h-32 w-32 text-3xl',
        default => 'h-12 w-12 text-sm',
    };

    $shapeClass = \App\Support\AvatarStyleOptions::shapeClass($shape);
    $ringClass = $showRing ? \App\Support\AvatarStyleOptions::ringClass($ring) : '';
    $filterClass = \App\Support\AvatarStyleOptions::filterClass($filter);
    $url = $user->avatarUrl();
@endphp

<span
    {{ $attributes->merge(['class' => "relative inline-flex shrink-0 items-center justify-center overflow-hidden bg-gradient-to-br from-violet-500 to-indigo-600 font-bold text-white shadow-inner shadow-violet-900/20 {$sizeClasses} {$shapeClass} {$ringClass}"]) }}
    role="img"
    aria-label="{{ __('Foto de perfil de :name', ['name' => $user->name]) }}"
>
    @if ($url)
        <img
            src="{{ $url }}"
            alt=""
            class="h-full w-full object-cover {{ $filterClass }}"
            loading="lazy"
            onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('hidden');"
        />
        <span class="hidden" aria-hidden="true">{{ $user->avatarInitials() }}</span>
    @else
        <span aria-hidden="true">{{ $user->avatarInitials() }}</span>
    @endif
</span>
