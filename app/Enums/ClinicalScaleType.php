<?php

namespace App\Enums;

enum ClinicalScaleType: string
{
    case Bai = 'bai';
    case Bdi = 'bdi';
    case Stress = 'stress';

    public function label(): string
    {
        return (string) config("clinical_scales.{$this->value}.label", $this->value);
    }

    public function description(): string
    {
        return (string) config("clinical_scales.{$this->value}.description", '');
    }

    public function icon(): string
    {
        return match ($this) {
            self::Bai => 'alert-triangle',
            self::Bdi => 'heart',
            self::Stress => 'activity',
        };
    }

    public function chartColor(): string
    {
        return match ($this) {
            self::Bai => 'rgba(245, 158, 11, 0.85)',
            self::Bdi => 'rgba(99, 102, 241, 0.85)',
            self::Stress => 'rgba(20, 184, 166, 0.85)',
        };
    }

    public static function tryFromRoute(string $scale): ?self
    {
        return self::tryFrom($scale);
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
