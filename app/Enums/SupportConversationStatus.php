<?php

namespace App\Enums;

enum SupportConversationStatus: string
{
    case Open = 'open';
    case PendingHuman = 'pending_human';
    case Assigned = 'assigned';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('Aberta'),
            self::PendingHuman => __('Aguarda atendente'),
            self::Assigned => __('Em atendimento'),
            self::Resolved => __('Resolvida'),
            self::Closed => __('Encerrada'),
        };
    }
}
