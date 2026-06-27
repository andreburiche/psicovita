<?php

namespace App\Models;

use App\Enums\TherapeuticGoalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientTherapeuticGoal extends Model
{
    protected $fillable = [
        'patient_id',
        'professional_id',
        'title',
        'description',
        'status',
        'progress_percent',
        'target_date',
        'achieved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TherapeuticGoalStatus::class,
            'target_date' => 'date',
            'achieved_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }
}
