<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotLog;
use App\Models\SupportConversation;

class ChatbotLogService
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function record(SupportConversation $conversation, string $event, ?array $payload = null): ChatbotLog
    {
        return ChatbotLog::query()->create([
            'support_conversation_id' => $conversation->id,
            'event' => $event,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
