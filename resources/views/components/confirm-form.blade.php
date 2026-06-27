@props([
    'title' => __('Confirmar ação'),
    'message' => __('Deseja continuar?'),
    'hint' => null,
    'eyebrow' => null,
    'confirmLabel' => __('Confirmar'),
    'cancelLabel' => __('Cancelar'),
    'variant' => 'danger',
    'details' => [],
    'validate' => true,
])

@php
    $formId = 'confirm-form-' . uniqid();
@endphp

<form
    {{ $attributes->merge(['id' => $formId]) }}
    x-data
    x-on:submit.prevent="
        @if ($validate) if (! $el.reportValidity()) return; @endif
        window.dispatchEvent(new CustomEvent('confirm-dialog:open', {
            detail: {
                title: @js($title),
                message: @js($message),
                hint: @js($hint),
                eyebrow: @js($eyebrow),
                confirmLabel: @js($confirmLabel),
                cancelLabel: @js($cancelLabel),
                variant: @js($variant),
                details: @js($details),
                formId: @js($formId),
            },
        }));
    "
>
    {{ $slot }}
</form>
