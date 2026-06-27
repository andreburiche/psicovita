<?php

namespace App\Enums;

enum InstitutionType: string
{
    case School = 'escola';
    case Doctor = 'medico';
    case Psychologist = 'psicologo';
    case SpeechTherapist = 'fonoaudiologo';
    case Company = 'empresa';
    case Other = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::School => 'Escola',
            self::Doctor => 'Médico',
            self::Psychologist => 'Psicólogo',
            self::SpeechTherapist => 'Fonoaudiólogo',
            self::Company => 'Empresa',
            self::Other => 'Outro',
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
