<?php

namespace App\Models;

use App\Enums\SupportMessageSenderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    protected $fillable = [
        'support_conversation_id',
        'sender_type',
        'sender_user_id',
        'body',
        'intent_slug',
        'metadata',
        'external_id',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
            'sender_type' => SupportMessageSenderType::class,
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'support_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
