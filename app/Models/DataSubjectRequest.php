<?php

namespace App\Models;

use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSubjectRequest extends Model
{
    protected $fillable = [
        'user_id',
        'patient_id',
        'type',
        'status',
        'details',
        'response_notes',
        'ip_address',
        'user_agent',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => DataSubjectRequestType::class,
            'status' => DataSubjectRequestStatus::class,
            'completed_at' => 'datetime',
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
