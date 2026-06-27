<?php

namespace App\Enums;

enum PaymentGateway: string
{
    case Manual = 'manual';
    case Asaas = 'asaas';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Asaas => 'Asaas',
        };
    }
}
