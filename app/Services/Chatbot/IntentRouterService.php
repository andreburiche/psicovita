<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotIntent;
use App\Models\SupportConversation;
use Illuminate\Support\Str;

class IntentRouterService
{
    public function __construct(
        private readonly ConversationAiService $conversationAi,
        private readonly ChatbotLogService $logs,
        private readonly ChatbotMenuService $menu,
    ) {}

    public function match(string $body, SupportConversation $conversation): ?ChatbotIntent
    {
        $normalized = Str::lower(Str::ascii(trim($body)));

        if ($normalized === '') {
            return null;
        }

        $menuIntent = $this->menu->matchMenuSelection($body, $conversation->user);
        if ($menuIntent !== null) {
            return $menuIntent;
        }

        $intent = $this->matchByPhrase($normalized);
        if ($intent !== null) {
            return $intent;
        }

        $intent = $this->conversationAi->matchIntent($body);
        if ($intent === null) {
            return null;
        }

        $this->logs->record($conversation, 'intent_ai_matched', [
            'intent' => $intent->slug,
            'confidence' => $intent->getAttribute('ai_confidence'),
        ]);

        return $intent;
    }

    private function matchByPhrase(string $normalized): ?ChatbotIntent
    {
        $intents = ChatbotIntent::query()
            ->where('is_active', true)
            ->whereHas('flow', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('priority')
            ->with(['responses', 'targetQueue'])
            ->get();

        foreach ($intents as $intent) {
            foreach ($intent->training_phrases as $phrase) {
                $needle = Str::lower(Str::ascii((string) $phrase));
                if ($needle !== '' && Str::contains($normalized, $needle)) {
                    return $intent;
                }
            }
        }

        return null;
    }
}
