<?php

namespace App\Enums;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => __('Mensal'),
            self::Yearly => __('Anual'),
        };
    }

    public function asaasCycle(): string
    {
        return match ($this) {
            self::Monthly => 'MONTHLY',
            self::Yearly => 'YEARLY',
        };
    }

    public function periodLabel(): string
    {
        return match ($this) {
            self::Monthly => __('mês'),
            self::Yearly => __('ano'),
        };
    }
}
