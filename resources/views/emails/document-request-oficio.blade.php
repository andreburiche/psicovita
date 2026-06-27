<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Solicitação de documentos') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2ff;font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#eef2ff;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 12px 32px rgba(91,33,182,0.1);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0284c7,#6366f1);padding:24px 28px;color:#fff;">
                            <p style="margin:0;font-size:20px;font-weight:800;">{{ $appName }}</p>
                            <p style="margin:8px 0 0;font-size:13px;opacity:0.95;">{{ __('Solicitação de documentos') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;color:#334155;font-size:15px;line-height:1.65;">
                            <p style="margin:0 0 16px;">{{ __('Prezados(as),') }}</p>

                            @if ($customMessage)
                                <p style="margin:0 0 16px;white-space:pre-wrap;">{{ $customMessage }}</p>
                            @else
                                <p style="margin:0 0 16px;">
                                    {{ __('Segue em anexo o ofício formal solicitando documentos referentes ao(a) paciente :patient, em acompanhamento psicológico.', ['patient' => $documentRequest->patient->name]) }}
                                </p>
                            @endif

                            <p style="margin:0 0 8px;"><strong>{{ __('Instituição:') }}</strong> {{ $documentRequest->institution_name }}</p>
                            <p style="margin:0 0 16px;"><strong>{{ __('Data da solicitação:') }}</strong> {{ $documentRequest->request_date->format('d/m/Y') }}</p>

                            <p style="margin:0 0 16px;">{{ __('Permanecemos à disposição para esclarecimentos.') }}</p>

                            <p style="margin:0;">
                                {{ __('Atenciosamente,') }}<br>
                                <strong>{{ $professional->name }}</strong>
                                @if ($professional->crp_number)
                                    <br>{{ __('CRP') }} {{ $professional->crp_number }}
                                @endif
                                <br>{{ $professional->email }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background:#f8fafc;font-size:11px;color:#64748b;">
                            {{ __('Mensagem enviada pelo sistema :app. Os dados tratados observam a LGPD (Lei 13.709/2018).', ['app' => $appName]) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
