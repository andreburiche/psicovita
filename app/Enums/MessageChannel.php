<?php

namespace App\Enums;

enum MessageChannel: string
{
    case Internal = 'internal';
    case Whatsapp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Internal => __('PsiConecta'),
            self::Whatsapp => __('WhatsApp'),
        };
    }
}
