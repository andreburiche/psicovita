<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Paid => 'Pago',
            self::Overdue => 'Em atraso',
            self::Cancelled => 'Cancelado',
            self::Refunded => 'Reembolsado',
        };
    }
}
