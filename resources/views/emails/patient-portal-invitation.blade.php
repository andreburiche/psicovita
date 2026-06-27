<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} — {{ __('Portal do paciente') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2ff;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#eef2ff;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 16px 48px rgba(91,33,182,0.12);">
                    <tr>
                        <td style="padding:32px;font-family:system-ui,sans-serif;color:#334155;">
                            <p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">{{ __('Bem-vindo(a) ao portal') }}</p>
                            <p style="margin:0 0 12px;line-height:1.6;">
                                {{ __(':professional criou o seu acesso ao portal do :app para acompanhar sessões, mensagens e documentos.', [
                                    'professional' => $professionalName,
                                    'app' => $appName,
                                ]) }}
                            </p>
                            <p style="margin:0 0 24px;line-height:1.6;">{{ __('Defina a sua palavra-passe no link abaixo. O convite expira em :date.', ['date' => $expiresAt]) }}</p>
                            <a href="{{ $activateUrl }}" style="display:inline-block;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;text-decoration:none;padding:14px 24px;border-radius:12px;font-weight:700;font-size:15px;">{{ __('Activar acesso') }}</a>
                            <p style="margin:24px 0 0;font-size:12px;line-height:1.5;color:#64748b;">{{ __('Se não reconhece este convite, ignore este e-mail.') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
