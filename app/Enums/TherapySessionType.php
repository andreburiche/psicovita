<?php

namespace App\Enums;

enum TherapySessionType: string
{
    case Online = 'online';
    case InPerson = 'in_person';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::InPerson => 'Presencial',
        };
    }
}
