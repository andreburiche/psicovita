<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnamnesisForm extends Model
{
    /**
     * @param  mixed  $value
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        $query = static::query()->where($field, $value);

        $user = auth()->user();
        if ($user?->isProfessional()) {
            $query->where('professional_id', $user->id);
        }

        return $query->firstOrFail();
    }

    protected $fillable = [
        'professional_id',
        'title',
        'description',
    ];

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AnamnesisQuestion::class)->orderBy('sort_order');
    }

    public function patientAnamneses(): HasMany
    {
        return $this->hasMany(PatientAnamnesis::class, 'anamnesis_form_id');
    }
}
