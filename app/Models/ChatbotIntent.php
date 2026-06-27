<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotIntent extends Model
{
    protected $fillable = [
        'chatbot_flow_id',
        'slug',
        'label',
        'training_phrases',
        'route_action',
        'target_queue_id',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'training_phrases' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ChatbotFlow::class, 'chatbot_flow_id');
    }

    public function targetQueue(): BelongsTo
    {
        return $this->belongsTo(SupportQueue::class, 'target_queue_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ChatbotResponse::class);
    }

    public function requiresHandoff(): bool
    {
        return $this->route_action === 'handoff';
    }
}
