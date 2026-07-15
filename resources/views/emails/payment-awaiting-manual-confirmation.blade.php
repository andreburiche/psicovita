<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} — {{ __('Pagamento aguardando confirmação') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2ff;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#eef2ff;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 16px 48px rgba(91,33,182,0.12);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#059669,#0d9488);padding:28px 32px;color:#fff;font-family:system-ui,sans-serif;">
                            <p style="margin:0;font-size:22px;font-weight:800;">{{ $appName }}</p>
                            <p style="margin:10px 0 0;font-size:13px;opacity:0.9;">{{ __('PIX manual') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;font-family:system-ui,sans-serif;color:#334155;">
                            <p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">{{ __('Olá, :name!', ['name' => $userName]) }}</p>
                            <p style="margin:0 0 24px;line-height:1.6;">
                                {{ __(':patient indicou que efectuou o pagamento de R$ :amount via PIX. Confirme o recebimento no painel.', [
                                    'patient' => $patientName,
                                    'amount' => $amount,
                                ]) }}
                            </p>
                            <a href="{{ $paymentUrl }}" style="display:inline-block;background:linear-gradient(135deg,#059669,#0d9488);color:#fff;text-decoration:none;padding:14px 24px;border-radius:12px;font-weight:700;font-size:15px;">{{ __('Abrir pagamento') }}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
