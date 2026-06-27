<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotResponse extends Model
{
    protected $fillable = [
        'chatbot_intent_id',
        'locale',
        'body_template',
        'quick_replies',
    ];

    protected function casts(): array
    {
        return [
            'quick_replies' => 'array',
        ];
    }

    public function intent(): BelongsTo
    {
        return $this->belongsTo(ChatbotIntent::class, 'chatbot_intent_id');
    }
}
