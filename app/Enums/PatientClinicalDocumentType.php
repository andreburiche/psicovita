<?php

namespace App\Enums;

enum PatientClinicalDocumentType: string
{
    case Atestado = 'atestado';
    case Declaracao = 'declaracao';
    case Receita = 'receita';

    public function label(): string
    {
        return match ($this) {
            self::Atestado => __('Atestado'),
            self::Declaracao => __('Declaração'),
            self::Receita => __('Receita (Prescrição)'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Atestado => __('Comparecimento ou afastamento para escola, trabalho ou instituições.'),
            self::Declaracao => __('Texto declaratório sobre acompanhamento ou situação clínica.'),
            self::Receita => __('Prescrição de medicamentos ou orientações farmacológicas.'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Atestado => 'document-text',
            self::Declaracao => 'clipboard',
            self::Receita => 'stethoscope',
        };
    }

    public function pdfTitle(): string
    {
        return match ($this) {
            self::Atestado => __('ATESTADO PSICOLÓGICO'),
            self::Declaracao => __('DECLARAÇÃO'),
            self::Receita => __('RECEITUÁRIO / PRESCRIÇÃO'),
        };
    }

    public static function tryFromRoute(string $type): ?self
    {
        return self::tryFrom($type);
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
