<?php

namespace App\Enums;

enum DataSubjectRequestStatus: string
{
    case Pending = 'pendente';
    case InProgress = 'em_andamento';
    case Completed = 'concluido';
    case Rejected = 'rejeitado';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pendente'),
            self::InProgress => __('Em andamento'),
            self::Completed => __('Concluído'),
            self::Rejected => __('Rejeitado'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
            self::InProgress => 'bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-200',
            self::Completed => 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
            self::Rejected => 'bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200',
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
