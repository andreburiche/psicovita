<?php

namespace App\Enums;

enum TherapeuticGoalStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Achieved = 'achieved';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pendente'),
            self::InProgress => __('Em progresso'),
            self::Achieved => __('Alcançado'),
            self::Cancelled => __('Cancelado'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
