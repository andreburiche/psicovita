<?php

namespace App\Models;

use App\Enums\SupportConversationStatus;
use App\Enums\SupportSourceChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportConversation extends Model
{
    protected $fillable = [
        'user_id',
        'support_queue_id',
        'assigned_agent_id',
        'status',
        'protocol_number',
        'source_channel',
        'whatsapp_phone_hash',
        'bot_active',
        'bot_context',
        'ai_summary',
        'sentiment_score',
        'first_response_at',
        'resolved_at',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupportConversationStatus::class,
            'source_channel' => SupportSourceChannel::class,
            'bot_active' => 'boolean',
            'bot_context' => 'array',
            'sentiment_score' => 'decimal:3',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(SupportQueue::class, 'support_queue_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ChatbotLog::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [
            SupportConversationStatus::Open,
            SupportConversationStatus::PendingHuman,
            SupportConversationStatus::Assigned,
        ], true);
    }
}
