<?php

namespace App\Enums;

enum SupportSourceChannel: string
{
    case WebWidget = 'web_widget';
    case Whatsapp = 'whatsapp';
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::WebWidget => __('Chat web'),
            self::Whatsapp => __('WhatsApp'),
            self::Internal => __('Interno'),
        };
    }
}
