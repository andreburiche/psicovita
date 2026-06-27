@props([
    'variant' => 'topbar',
    'showTagline' => false,
    /** Quando true (menu lateral da app), o texto pode ocultar-se em modo ícone (Alpine). */
    'collapsible' => false,
    /** Logo branca (ex.: painel roxo do login) via filter: brightness(0) invert(1). */
    'inverted' => false,
    /** Sobrescreve o rótulo da área (ex.: Área clínica, Área do paciente). */
    'taglineLabel' => null,
])

@php
    $logoPath = config('app.logo', 'images/Logo.png');
    $logoUrl = asset($logoPath);
    $logoExists = is_file(public_path($logoPath));
    $markUrl = asset('images/brand-mark.svg');
    $logoAlt = (string) config('app.logo_alt', config('app.name'));

    $imgFilterClass = $inverted ? 'brightness-0 invert' : '';

    $imgExpandedClass = match ($variant) {
        'auth' => 'h-24 w-auto max-w-[280px] object-contain sm:h-28 sm:max-w-[320px]',
        'guest' => 'h-24 w-auto max-w-[280px] object-contain sm:h-28 sm:max-w-[320px]',
        'landing' => 'h-14 w-auto max-w-[200px] object-contain sm:h-16',
        'sidebar' => 'h-11 w-auto max-w-[180px] object-contain object-left',
        'patient' => 'h-12 w-auto max-w-[180px] object-contain',
        default => 'h-9 w-auto max-w-[160px] object-contain',
    };

    $taglineClass = match ($variant) {
        'sidebar' => 'text-[10px] font-bold uppercase tracking-[0.2em] text-violet-200',
        'patient' => 'text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-400',
        default => 'text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400',
    };

    $taglineText = $taglineLabel ?? ($variant === 'patient'
        ? __('O seu espaço')
        : __('Área clínica'));

    $stackTagline = $showTagline && in_array($variant, ['patient', 'sidebar'], true);
@endphp

<div
    {{ $attributes->class([
        'inline-flex max-w-full min-w-0 items-center gap-2 sm:gap-2.5',
        'flex-col items-start gap-1' => $stackTagline && ! ($variant === 'sidebar' && $collapsible),
    ]) }}
    @if ($variant === 'sidebar' && $collapsible)
        x-bind:class="navLabelsVisible() ? 'flex-row' : 'lg:justify-center'"
    @endif
>
    @if ($variant === 'sidebar' && $collapsible)
        {{-- Expandido: logo completa (PNG se existir) ou marca SVG --}}
        <template x-if="navLabelsVisible()">
            @if ($logoExists)
                <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" class="{{ trim("{$imgExpandedClass} {$imgFilterClass}") }}" decoding="async" />
            @else
                <img src="{{ $markUrl }}" alt="{{ $logoAlt }}" @class(['h-10 w-10 shrink-0 object-contain', $imgFilterClass]) decoding="async" />
            @endif
        </template>
        {{-- Recolhido: ícone da marca (parte esquerda do logo) --}}
        <template x-if="! navLabelsVisible()">
            @if ($logoExists)
                <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" @class(['h-10 w-10 shrink-0 object-contain object-left', $imgFilterClass]) decoding="async" />
            @else
                <x-ui.icon name="brand-psi" class="h-10 w-10 shrink-0 text-violet-500" />
            @endif
        </template>
        @if ($showTagline)
            <span class="min-w-0 truncate {{ $taglineClass }}" x-show="navLabelsVisible()" x-transition.opacity>{{ $taglineText }}</span>
        @endif
    @else
        @if ($logoExists)
            <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" class="{{ trim("{$imgExpandedClass} {$imgFilterClass}") }}" decoding="async" />
        @else
            <img src="{{ $markUrl }}" alt="{{ $logoAlt }}" @class([$imgExpandedClass, $imgFilterClass, 'h-10 w-10' => $variant === 'sidebar']) decoding="async" />
        @endif
        @if ($showTagline)
            <span class="min-w-0 truncate {{ $taglineClass }}">{{ $taglineText }}</span>
        @endif
    @endif
</div>
