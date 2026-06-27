<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Pix = 'pix';
    case Card = 'card';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Pix => 'PIX',
            self::Card => 'Cartão',
            self::Manual => __('Manual (admin)'),
        };
    }
}
