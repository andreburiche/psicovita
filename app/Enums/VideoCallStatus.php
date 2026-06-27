<?php

namespace App\Enums;

enum VideoCallStatus: string
{
    case Pending = 'pending';
    case Live = 'live';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Aguardando início'),
            self::Live => __('Em andamento'),
            self::Ended => __('Encerrada'),
        };
    }
}
