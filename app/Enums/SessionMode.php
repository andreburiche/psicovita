<?php

namespace App\Enums;

enum SessionMode: string
{
    case Individual = 'individual';
    case WithObserver = 'with_observer';
    case Family = 'family';
    case Group = 'group';

    public function label(): string
    {
        return match ($this) {
            self::Individual => __('Individual (1 paciente)'),
            self::WithObserver => __('Com escuta / supervisão'),
            self::Family => __('Casal / família'),
            self::Group => __('Grupo terapêutico'),
        };
    }

    public function supportsMultiplePatients(): bool
    {
        return $this === self::Group;
    }

    public function supportsGuests(): bool
    {
        return in_array($this, [self::Family, self::Group], true);
    }

    public function supportsObserver(): bool
    {
        return $this === self::WithObserver;
    }
}
