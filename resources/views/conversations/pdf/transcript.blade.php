<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ __('Transcrição de conversa') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; }
        h1 { font-size: 16px; margin: 0 0 8px; color: #5b21b6; }
        .meta { font-size: 10px; color: #64748b; margin-bottom: 16px; }
        pre { white-space: pre-wrap; word-wrap: break-word; font-family: DejaVu Sans, sans-serif; font-size: 10px; background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0; border-radius: 4px; }
        .footer { margin-top: 24px; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>{{ __('Transcrição de conversa terapêutica') }}</h1>
    <p class="meta">
        {{ __('Paciente') }}: {{ $conversation->patient?->name ?? $conversation->patientUser?->name ?? '—' }} ·
        {{ __('Profissional') }}: {{ $conversation->professional?->name ?? '—' }} ·
        {{ __('Exportado') }}: {{ $exportedAt->format('d/m/Y H:i') }}
    </p>
    <pre>{{ $transcript }}</pre>
    <p class="footer">
        {{ __('Documento gerado pelo :app. Conteúdo confidencial sujeito a sigilo profissional.', ['app' => config('app.name')]) }}
    </p>
</body>
</html>
