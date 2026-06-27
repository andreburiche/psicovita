@props([])

<div {{ $attributes->merge(['class' => 'mx-auto max-w-5xl space-y-6']) }}>
    {{ $slot }}
</div>
