<?php

namespace App\Enums;

enum DocumentRequestStatus: string
{
    case Pending = 'pendente';
    case Sent = 'enviado';
    case Answered = 'respondido';
    case Cancelled = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Sent => 'Enviado',
            self::Answered => 'Respondido',
            self::Cancelled => 'Cancelado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
            self::Sent => 'bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-200',
            self::Answered => 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
            self::Cancelled => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
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
