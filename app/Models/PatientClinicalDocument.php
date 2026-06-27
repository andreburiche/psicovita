<?php

namespace App\Models;

use App\Enums\PatientClinicalDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientClinicalDocument extends Model
{
    protected $fillable = [
        'patient_id',
        'professional_id',
        'type',
        'issued_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'type' => PatientClinicalDocumentType::class,
            'issued_at' => 'date',
            'payload' => 'encrypted:array',
        ];
    }

    /**
     * @param  mixed  $value
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        $query = static::query()->where($field, $value);

        $user = auth()->user();
        if ($user?->isProfessional()) {
            $query->where('professional_id', $user->clinicalPracticeId());
        }

        return $query->firstOrFail();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function placeLabel(): string
    {
        return (string) ($this->payload['place'] ?? '');
    }

    public function bodyText(): string
    {
        return (string) ($this->payload['body'] ?? '');
    }
}
