<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $appName }} — {{ __('Confirmar e-mail') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2ff;-webkit-text-size-adjust:100%;">
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
        {{ __('Confirme o seu e-mail para começar a usar o :app.', ['app' => $appName]) }}
    </div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#eef2ff;">
        <tr>
            <td align="center" style="padding:40px 16px 48px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;">
                    <tr>
                        <td style="border-radius:20px;overflow:hidden;background-color:#ffffff;box-shadow:0 16px 48px rgba(91,33,182,0.12);">
                            {{-- Cabeçalho --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td bgcolor="#5b21b6" style="background:linear-gradient(135deg,#7c3aed 0%,#4f46e5 55%,#4338ca 100%);background-color:#5b21b6;padding:28px 32px 26px;">
                                        <p style="margin:0;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:22px;font-weight:800;letter-spacing:-0.02em;color:#ffffff;">
                                            {{ $appName }}
                                        </p>
                                        <p style="margin:10px 0 0;font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,sans-serif;font-size:13px;line-height:1.5;color:rgba(255,255,255,0.9);">
                                            {{ __('Gestão clínica no seu consultório') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            {{-- Corpo --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding:32px 32px 8px;font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,sans-serif;">
                                        <p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;letter-spacing:-0.02em;">
                                            {{ __('Olá, :name!', ['name' => $userName]) }}
                                        </p>
                                        <p style="margin:0 0 12px;font-size:15px;line-height:1.65;color:#475569;">
                                            {{ __('Obrigado por se juntar ao :app. Para concluir o registo e aceder à área clínica, confirme o seu endereço de e-mail.', ['app' => $appName]) }}
                                        </p>
                                        <p style="margin:0 0 28px;font-size:15px;line-height:1.65;color:#475569;">
                                            {{ __('Utilize o botão abaixo — a ligação expira em segurança ao fim de algum tempo.') }}
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:0 32px 28px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td align="center" bgcolor="#7c3aed" style="border-radius:14px;background:linear-gradient(135deg,#7c3aed 0%,#6366f1 100%);background-color:#7c3aed;">
                                                    <a href="{{ $verificationUrl }}" target="_blank" rel="noopener" style="display:inline-block;padding:16px 36px;font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,sans-serif;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:14px;">
                                                        {{ __('Confirmar o meu e-mail') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 32px 32px;font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,sans-serif;">
                                        <p style="margin:0 0 12px;font-size:12px;line-height:1.6;color:#94a3b8;">
                                            {{ __('Se o botão não funcionar, copie e cole esta ligação no navegador:') }}
                                        </p>
                                        <p style="margin:0 0 24px;word-break:break-all;font-size:12px;line-height:1.5;color:#64748b;">
                                            <a href="{{ $verificationUrl }}" style="color:#7c3aed;text-decoration:underline;">{{ $verificationUrl }}</a>
                                        </p>
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-top:1px solid #e2e8f0;">
                                            <tr>
                                                <td style="padding-top:20px;">
                                                    <p style="margin:0;font-size:12px;line-height:1.65;color:#94a3b8;">
                                                        {{ __('Se não criou uma conta no :app, pode ignorar este e-mail com segurança.', ['app' => $appName]) }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style="margin:20px 0 0;font-size:12px;color:#cbd5e1;">
                                            {{ __('Com os melhores cumprimentos,') }}<br>
                                            <span style="color:#64748b;font-weight:600;">{{ $appName }}</span>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:24px 16px 0;font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,sans-serif;font-size:11px;color:#94a3b8;line-height:1.5;">
                            {{ __('Mensagem automática. Por favor não responda a este e-mail.') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
