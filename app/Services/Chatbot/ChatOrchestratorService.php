<?php

namespace App\Services\Chatbot;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;

class ChatOrchestratorService
{
    public function __construct(
        private readonly SupportConversationService $conversations,
        private readonly ChatbotEngineService $bot,
        private readonly ChatbotLogService $logs,
        private readonly ChatbotMenuService $menu,
    ) {}

    public function bootstrap(User $user): SupportConversation
    {
        $conversation = $this->conversations->findOrCreateForUser($user);

        if ($conversation->messages()->count() === 0) {
            $this->conversations->sendBotMessage(
                $conversation,
                $this->menu->welcomeMessage($user),
                'greeting',
            );

            $this->logs->record($conversation, 'conversation_started');
        }

        return $conversation->fresh(['messages', 'queue']);
    }

    public function handleUserMessage(User $user, string $body): array
    {
        $conversation = $this->conversations->findOrCreateForUser($user);
        $userMessage = $this->conversations->sendUserMessage($conversation, $user, $body);

        $this->logs->record($conversation, 'user_message', [
            'message_id' => $userMessage->id,
        ]);

        $botMessage = null;
        if ($conversation->bot_active) {
            $botMessage = $this->bot->reply($conversation->fresh(), $body);
        }

        return [
            'conversation' => $conversation->fresh(['queue']),
            'user_message' => $userMessage,
            'bot_message' => $botMessage,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function messagesPayload(SupportConversation $conversation, int $afterId = 0): array
    {
        return $conversation->messages()
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->get()
            ->map(fn (SupportMessage $message) => $this->serializeMessage($message))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMessage(SupportMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type->value,
            'sender_label' => $message->sender_type->label(),
            'body' => $message->body,
            'intent_slug' => $message->intent_slug,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeConversation(SupportConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'protocol_number' => $conversation->protocol_number,
            'status' => $conversation->status->value,
            'status_label' => $conversation->status->label(),
            'bot_active' => $conversation->bot_active,
            'queue' => $conversation->queue?->only(['slug', 'name']),
        ];
    }
}
