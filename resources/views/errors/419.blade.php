@php
    $code = '419';
    $title = __('Sessão expirada');
    $message = __('A sua sessão expirou por segurança (formulário antigo ou cookies limpos). Volte atrás e tente novamente.');
    $icon = 'clock';
@endphp

@include('errors.layout')
