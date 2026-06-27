{{ __('Olá,') }}

{{ __('Sua solicitação LGPD do tipo “:type” foi atualizada para o status: :status.', [
    'type' => $dataSubjectRequest->type->label(),
    'status' => $dataSubjectRequest->status->label(),
]) }}

@if ($dataSubjectRequest->response_notes)
{{ __('Resposta do encarregado:') }}
{{ $dataSubjectRequest->response_notes }}
@endif

{{ __('Portal:') }} {{ $portalUrl }}

{{ __('Enviado por :app', ['app' => $appName]) }}
