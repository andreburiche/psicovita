<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnamnesisQuestion extends Model
{
    protected $fillable = [
        'anamnesis_form_id',
        'label',
        'field_key',
        'field_type',
        'sort_order',
        'required',
        'mask',
        'validation_rules',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'validation_rules' => 'array',
            'meta' => 'array',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(AnamnesisForm::class, 'anamnesis_form_id');
    }
}
