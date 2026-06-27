<?php

namespace App\Models;

use App\Enums\AiRequestStatus;
use App\Enums\AiRequestType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequest extends Model
{
    protected $fillable = [
        'user_id',
        'patient_id',
        'type',
        'input_text',
        'output_text',
        'approach',
        'status',
        'tokens_used',
        'lgpd_consent_at',
        'lgpd_consent_ip',
    ];

    protected function casts(): array
    {
        return [
            'type' => AiRequestType::class,
            'status' => AiRequestStatus::class,
            'lgpd_consent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
