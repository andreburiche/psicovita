<?php

namespace App\Enums;

enum AiRequestStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pendente'),
            self::Completed => __('Concluído'),
            self::Failed => __('Falhou'),
        };
    }
}
