<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'Período de teste',
            self::Active => 'Ativa',
            self::PastDue => 'Pagamento em atraso',
            self::Cancelled => 'Cancelada',
            self::Expired => 'Expirada',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Trialing => 'bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-200',
            self::Active => 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
            self::PastDue => 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
            self::Cancelled => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            self::Expired => 'bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
