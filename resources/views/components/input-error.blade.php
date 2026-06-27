@props(['messages', 'id' => null])

@php
    $errorId = $id ?? 'field-error-' . uniqid();
@endphp

@if ($messages)
    <ul
        {{ $attributes->merge(['class' => 'text-sm text-red-600 dark:text-red-400 space-y-1']) }}
        id="{{ $errorId }}"
        role="alert"
        aria-live="polite"
    >
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
