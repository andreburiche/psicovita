<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAnamnesis extends Model
{
    protected $table = 'patient_anamneses';

    protected $fillable = [
        'patient_id',
        'anamnesis_form_id',
        'professional_id',
        'answers',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'encrypted:array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(AnamnesisForm::class, 'anamnesis_form_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }
}
