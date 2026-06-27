<?php

namespace App\Enums;

enum SubscriptionPlanSlug: string
{
    case Trial = 'trial';
    case Essencial = 'essencial';
    case Premium = 'premium';
    case Clinica = 'clinica';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Trial',
            self::Essencial => 'Essencial',
            self::Premium => 'Premium',
            self::Clinica => 'Clínica',
        };
    }
}
