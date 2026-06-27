<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportQueue extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'sla_first_response_minutes',
        'sla_resolution_minutes',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(SupportConversation::class);
    }

    public function intents(): HasMany
    {
        return $this->hasMany(ChatbotIntent::class, 'target_queue_id');
    }
}
