<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotFlow extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function intents(): HasMany
    {
        return $this->hasMany(ChatbotIntent::class);
    }
}
