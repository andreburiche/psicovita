<?php

namespace App\Support;

final class ExternalContactUrls
{
    /**
     * Ligação mailto: com assunto e corpo opcionais (RFC 6068).
     */
    public static function mailto(?string $email, ?string $subject = null, ?string $body = null): ?string
    {
        $email = $email !== null ? trim($email) : '';
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $parts = [];
        if ($subject !== null && $subject !== '') {
            $parts[] = 'subject='.rawurlencode($subject);
        }
        if ($body !== null && $body !== '') {
            $parts[] = 'body='.rawurlencode($body);
        }

        return 'mailto:'.$email.(count($parts) > 0 ? '?'.implode('&', $parts) : '');
    }

    /**
     * Abre conversa no WhatsApp (wa.me). O número deve incluir indicativo internacional (só dígitos).
     * Opcional: prefixo em WHATSAPP_DEFAULT_CALLING_CODE (ex.: 351) se o telefone na ficha for nacional.
     */
    public static function whatsapp(?string $phone, ?string $prefilledText = null): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        $cc = preg_replace('/\D+/', '', (string) config('psiconecta.whatsapp.default_calling_code', '')) ?? '';
        if ($cc !== '' && ! str_starts_with($digits, $cc)) {
            $digits = $cc.$digits;
        }

        $url = 'https://wa.me/'.$digits;
        if ($prefilledText !== null && $prefilledText !== '') {
            $url .= '?text='.rawurlencode($prefilledText);
        }

        return $url;
    }
}
