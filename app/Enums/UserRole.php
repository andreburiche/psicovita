<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Professional = 'professional';
    case Patient = 'patient';
    case SupportAgent = 'support_agent';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Professional => 'Profissional',
            self::Patient => 'Paciente',
            self::SupportAgent => 'Atendente',
        };
    }
}
