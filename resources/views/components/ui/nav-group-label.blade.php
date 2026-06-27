@props(['label'])

<p
    {{ $attributes->merge(['class' => 'px-3 pb-1 pt-4 text-[10px] font-bold uppercase tracking-[0.18em] text-violet-300/70 first:pt-2']) }}
    x-show="navLabelsVisible()"
    x-transition.opacity
>{{ $label }}</p>
