@php
    $code = '500';
    $title = __('Erro interno do servidor');
    $message = __('Ocorreu um problema inesperado. A equipa técnica foi notificada. Tente novamente dentro de momentos.');
    $icon = 'alert-circle';
@endphp

@include('errors.layout')
