<?php

namespace App\Enums;

enum PaymentMethodPreference: string
{
    case Auto = 'auto';
    case Asaas = 'asaas';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Auto => __('Automático (recomendado)'),
            self::Asaas => __('Forçar Asaas'),
            self::Manual => __('Forçar PIX manual'),
        };
    }
}
