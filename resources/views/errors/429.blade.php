@php
    $code = '429';
    $title = __('Demasiados pedidos');
    $message = __('Fez demasiados pedidos em pouco tempo. Aguarde alguns minutos e tente novamente.');
    $icon = 'alert-triangle';
@endphp

@include('errors.layout')
