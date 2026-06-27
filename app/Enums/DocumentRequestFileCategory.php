<?php

namespace App\Enums;

enum DocumentRequestFileCategory: string
{
    case Authorization = 'autorizacao';
    case InstitutionResponse = 'resposta_instituicao';
    case ComplementaryReport = 'relatorio_complementar';

    public function label(): string
    {
        return match ($this) {
            self::Authorization => 'Autorização assinada',
            self::InstitutionResponse => 'Documento da instituição',
            self::ComplementaryReport => 'Relatório complementar',
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
