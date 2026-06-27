<?php

namespace App\Enums;

enum VideoRecordingStatus: string
{
    case None = 'none';
    case Recording = 'recording';
    case Uploaded = 'uploaded';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::None => __('Sem gravação'),
            self::Recording => __('Gravando'),
            self::Uploaded => __('Gravação recebida'),
            self::Processing => __('Processando com IA'),
            self::Completed => __('Pronta para revisão'),
            self::Failed => __('Falha no processamento'),
        };
    }
}
