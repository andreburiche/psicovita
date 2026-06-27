@props(['status'])

@if ($status)
    <x-flash-alert type="success" :message="$status" :dismissible="false" {{ $attributes }} />
@endif
