<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ __('Solicitação LGPD') }}</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1e293b;">
    <p>{{ __('Olá, :name', ['name' => $dpoName]) }}</p>

    <p>{{ __('Foi registrada uma nova solicitação de direitos do titular no :app.', ['app' => $appName]) }}</p>

    <ul>
        <li><strong>{{ __('Titular:') }}</strong> {{ $requester->name }} ({{ $requester->email }})</li>
        <li><strong>{{ __('Tipo:') }}</strong> {{ $dataSubjectRequest->type->label() }}</li>
        <li><strong>{{ __('Status:') }}</strong> {{ $dataSubjectRequest->status->label() }}</li>
        <li><strong>{{ __('Data:') }}</strong> {{ $dataSubjectRequest->created_at->format('d/m/Y H:i') }}</li>
        @if ($dataSubjectRequest->patient)
            <li><strong>{{ __('Ficha:') }}</strong> {{ $dataSubjectRequest->patient->name }} (#{{ $dataSubjectRequest->patient_id }})</li>
        @endif
    </ul>

    @if ($dataSubjectRequest->details)
        <p><strong>{{ __('Detalhes:') }}</strong></p>
        <p style="white-space: pre-wrap;">{{ $dataSubjectRequest->details }}</p>
    @endif

    <p>
        <a href="{{ route('admin.lgpd.requests.show', $dataSubjectRequest) }}">{{ __('Abrir no painel LGPD') }}</a>
    </p>

    <p>{{ __('Responda diretamente ao titular utilizando o e-mail de resposta desta mensagem.') }}</p>
</body>
</html>
