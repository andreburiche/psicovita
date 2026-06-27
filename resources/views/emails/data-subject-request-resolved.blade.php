<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1e293b;">
    <p>{{ __('Olá,') }}</p>

    <p>{{ __('Sua solicitação LGPD do tipo “:type” foi atualizada para o status: :status.', [
        'type' => $dataSubjectRequest->type->label(),
        'status' => $dataSubjectRequest->status->label(),
    ]) }}</p>

    @if ($dataSubjectRequest->response_notes)
        <p><strong>{{ __('Resposta do encarregado:') }}</strong></p>
        <p style="white-space: pre-wrap;">{{ $dataSubjectRequest->response_notes }}</p>
    @endif

    <p>
        <a href="{{ $portalUrl }}">{{ __('Ver no portal de privacidade') }}</a>
    </p>

    <p style="font-size: 12px; color: #64748b;">{{ __('Enviado por :app', ['app' => $appName]) }}</p>
</body>
</html>
