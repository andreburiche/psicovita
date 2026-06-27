<?php

namespace App\Enums;

enum UserProfessionalFileCategory: string
{
    case Curriculum = 'curriculum';
    case Certificate = 'certificate';
    case License = 'license';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Curriculum => __('Currículo'),
            self::Certificate => __('Certificado'),
            self::License => __('Licença / registro'),
            self::Other => __('Outro'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
