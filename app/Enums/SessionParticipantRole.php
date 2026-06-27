<?php

namespace App\Enums;

enum SessionParticipantRole: string
{
    case Host = 'host';
    case Patient = 'patient';
    case Guest = 'guest';
    case Observer = 'observer';

    public function label(): string
    {
        return match ($this) {
            self::Host => __('Profissional'),
            self::Patient => __('Paciente'),
            self::Guest => __('Convidado'),
            self::Observer => __('Observador (escuta)'),
        };
    }

    public function joinsMuted(): bool
    {
        return $this === self::Observer;
    }

    public function mustConsentForRecording(): bool
    {
        return in_array($this, [self::Host, self::Patient, self::Observer, self::Guest], true);
    }
}
