@props([
    'patient',
    'size' => 'md',
    'showRing' => true,
])

@php
    use App\Support\AvatarStyleOptions;

    $resolved = $patient->resolvedAvatarStyle();
    $shape = $resolved['shape'];
    $ring = $resolved['ring'];
    $filter = $resolved['filter'];

    $sizeClasses = match ($size) {
        'xs' => 'h-8 w-8 text-xs',
        'sm' => 'h-10 w-10 text-sm',
        'lg' => 'h-24 w-24 text-2xl',
        'xl' => 'h-32 w-32 text-3xl',
        'hero' => 'h-20 w-20 text-2xl sm:h-24 sm:w-24 sm:text-3xl',
        'list' => 'h-14 w-14 text-lg',
        default => 'h-12 w-12 text-sm',
    };

    $shapeClass = AvatarStyleOptions::shapeClass($shape);
    $ringClass = $showRing ? AvatarStyleOptions::ringClass($ring) : '';
    $filterClass = AvatarStyleOptions::filterClass($filter);
    $url = $patient->avatarUrl();
    $owner = $patient->avatarOwner();
    $cacheBust = ($owner instanceof \App\Models\User ? $owner->updated_at : $patient->updated_at)?->timestamp ?? time();
@endphp

<span
    {{ $attributes->merge(['class' => "relative inline-flex shrink-0 items-center justify-center overflow-hidden bg-gradient-to-br from-violet-500 to-indigo-600 font-bold text-white shadow-inner shadow-violet-900/20 {$sizeClasses} {$shapeClass} {$ringClass}"]) }}
    role="img"
    aria-label="{{ __('Foto de perfil de :name', ['name' => $patient->name]) }}"
>
    @if ($url)
        <img
            src="{{ $url }}?v={{ $cacheBust }}"
            alt=""
            class="h-full w-full object-cover {{ $filterClass }}"
            loading="lazy"
        />
    @else
        <span aria-hidden="true">{{ $patient->avatarInitials() }}</span>
    @endif
</span>
