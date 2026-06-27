<?php

namespace App\Models;

use App\Enums\ClinicalScaleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientScaleAssessment extends Model
{
    protected $fillable = [
        'patient_id',
        'professional_id',
        'scale_type',
        'assessed_at',
        'total_score',
        'severity',
        'severity_label',
        'is_risk',
        'responses',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scale_type' => ClinicalScaleType::class,
            'assessed_at' => 'date',
            'is_risk' => 'boolean',
            'responses' => 'encrypted:array',
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
