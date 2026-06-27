<?php

namespace App\Enums;

enum TherapySessionStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendada',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }
}
