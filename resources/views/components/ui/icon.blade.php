@props([
    'name',
    'label' => null,
])

@php
    $aliases = config('ui-icons.aliases', []);
    $icons = config('ui-icons.icons', []);
    $resolved = $aliases[$name] ?? $name;
    $icon = $icons[$resolved] ?? $icons['help'] ?? null;
    $class = $attributes->get('class', 'h-5 w-5');
    $viewBox = $icon['view_box'] ?? '0 0 24 24';
    $strokeWidth = $icon['stroke_width'] ?? '1.5';
    $isFilled = (bool) ($icon['filled'] ?? false);
@endphp

@if ($label)
    <span class="sr-only">{{ $label }}</span>
@endif

@if ($resolved === 'brand-psi')
    <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 40 40', 'fill' => 'none', 'aria-hidden' => $label ? null : 'true']) }}>
        <rect width="40" height="40" rx="12" fill="currentColor" class="text-violet-600"/>
        <path d="M20 8v24M12 14c0 4.5 3.2 8 8 8s8-3.5 8-8" stroke="#fff" stroke-width="2.75" stroke-linecap="round"/>
    </svg>
@elseif ($icon)
    <svg
        {{ $attributes->merge([
            'class' => $class,
            'viewBox' => $viewBox,
            'fill' => $isFilled ? 'currentColor' : 'none',
            'stroke' => $isFilled ? 'none' : 'currentColor',
            'stroke-width' => $isFilled ? null : $strokeWidth,
            'aria-hidden' => $label ? null : 'true',
        ]) }}
    >
        @foreach ($icon['paths'] as $path)
            @if (is_array($path))
                <path
                    d="{{ $path['d'] }}"
                    @if (! empty($path['fill_rule'])) fill-rule="{{ $path['fill_rule'] }}" @endif
                    @if (! empty($path['clip_rule'])) clip-rule="{{ $path['clip_rule'] }}" @endif
                    @if (! empty($path['stroke'])) stroke="{{ $path['stroke'] }}" fill="none" @endif
                    @if (! empty($path['stroke_width'])) stroke-width="{{ $path['stroke_width'] }}" @endif
                    @if (! empty($path['stroke_linecap'])) stroke-linecap="{{ $path['stroke_linecap'] }}" @endif
                />
            @else
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
            @endif
        @endforeach
    </svg>
@endif
