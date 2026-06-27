<?php

namespace App\Enums;

enum AiRequestType: string
{
    case Transcricao = 'transcricao';
    case TextoAbordagem = 'texto_abordagem';
    case RecomendacaoTerapeuta = 'recomendacao_terapeuta';

    public function label(): string
    {
        return match ($this) {
            self::Transcricao => __('Transcrição'),
            self::TextoAbordagem => __('Texto por abordagem'),
            self::RecomendacaoTerapeuta => __('Recomendação de terapeuta'),
        };
    }
}
