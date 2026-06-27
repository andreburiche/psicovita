<?php

namespace App\Models;

use App\Enums\PaymentGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayTransaction extends Model
{
    protected $fillable = [
        'payment_id',
        'gateway',
        'event_type',
        'external_id',
        'status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'gateway' => PaymentGateway::class,
            'payload' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
