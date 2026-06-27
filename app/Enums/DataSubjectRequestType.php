<?php

namespace App\Enums;

enum DataSubjectRequestType: string
{
    case Access = 'acesso';
    case Correction = 'correcao';
    case Deletion = 'eliminacao';
    case Portability = 'portabilidade';
    case Opposition = 'oposicao';
    case Revocation = 'revogacao';

    public function label(): string
    {
        return match ($this) {
            self::Access => __('Confirmação e acesso aos dados'),
            self::Correction => __('Correção de dados incompletos ou desatualizados'),
            self::Deletion => __('Eliminação de dados desnecessários'),
            self::Portability => __('Portabilidade dos dados'),
            self::Opposition => __('Oposição ao tratamento'),
            self::Revocation => __('Revogação de consentimento'),
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
