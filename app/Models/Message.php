<?php

namespace App\Models;

use App\Enums\MessageChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'recipient_id',
        'body',
        'channel',
        'external_id',
        'metadata',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
            'read_at' => 'datetime',
            'channel' => MessageChannel::class,
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function isFrom(User $user): bool
    {
        return $this->sender_id === $user->id;
    }
}
