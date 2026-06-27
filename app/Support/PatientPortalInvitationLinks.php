<?php

namespace App\Support;

use App\Models\PatientPortalInvitation;

class PatientPortalInvitationLinks
{
    public static function activationUrl(PatientPortalInvitation $invitation): string
    {
        $path = route('patient-portal.activate.show', $invitation->token, false);
        $base = rtrim((string) (config('patient_portal.public_app_url') ?: config('app.url')), '/');

        return $base.$path;
    }

    public static function isLocalhostUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    public static function whatsAppBody(
        string $professionalName,
        string $appName,
        string $activateUrl,
        string $expiresDate,
    ): string {
        $lines = [
            '*'.$appName.'*',
            __('Acesso ao portal do paciente'),
            '',
            __(':professional convidou-o(a) a activar a sua conta.', ['professional' => $professionalName]),
            '',
            __('Toque no link abaixo para definir a palavra-passe:'),
            '',
            $activateUrl,
            '',
            __('Válido até :date.', ['date' => $expiresDate]),
        ];

        if (self::isLocalhostUrl($activateUrl)) {
            $lines[] = '';
            $lines[] = __('Nota: links locais (127.0.0.1) não abrem no telemóvel. Defina APP_PUBLIC_URL no .env (ex.: túnel ngrok) para testar no WhatsApp.');
        }

        return implode("\n", $lines);
    }
}
