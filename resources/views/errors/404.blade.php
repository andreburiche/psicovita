@php
    $code = '404';
    $title = __('Página não encontrada');
    $message = __('O endereço que procurou não existe, foi removido ou o link está incorrecto.');
    $icon = 'search';
@endphp

@include('errors.layout')
