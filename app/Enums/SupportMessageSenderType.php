<?php

namespace App\Enums;

enum SupportMessageSenderType: string
{
    case User = 'user';
    case Bot = 'bot';
    case Agent = 'agent';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::User => __('Utilizador'),
            self::Bot => __('Assistente'),
            self::Agent => __('Atendente'),
            self::System => __('Sistema'),
        };
    }
}
