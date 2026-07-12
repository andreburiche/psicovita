@php
    $code = '403';
    $title = __('Acesso negado');
    $message = __('Não tem permissão para aceder a esta página. Se acredita que isto é um erro, contacte o suporte.');
    $icon = 'lock';
@endphp

@include('errors.layout')
