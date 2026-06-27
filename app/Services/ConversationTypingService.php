<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ConversationTypingService
{
    private const TTL_SECONDS = 5;

    public function pulse(Conversation $conversation, User $user): void
    {
        if (! $conversation->involvesUser($user)) {
            return;
        }

        Cache::put($this->cacheKey($conversation->id, $user->id), true, now()->addSeconds(self::TTL_SECONDS));
    }

    public function isPeerTyping(Conversation $conversation, User $viewer): bool
    {
        $peer = $conversation->peerFor($viewer);

        if ($peer === null) {
            return false;
        }

        return Cache::has($this->cacheKey($conversation->id, $peer->id));
    }

    private function cacheKey(int $conversationId, int $userId): string
    {
        return "conversation.{$conversationId}.typing.{$userId}";
    }
}
