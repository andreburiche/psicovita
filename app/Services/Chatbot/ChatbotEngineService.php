<?php

namespace App\Services\Chatbot;

use App\Enums\SupportConversationStatus;
use App\Models\ChatbotIntent;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Support\Str;

class ChatbotEngineService
{
    public function __construct(
        private readonly IntentRouterService $router,
        private readonly SupportConversationService $conversations,
        private readonly ChatbotLogService $logs,
        private readonly ConversationAiService $conversationAi,
        private readonly ChatbotMenuService $menu,
    ) {}

    public function reply(SupportConversation $conversation, string $userBody): SupportMessage
    {
        $intent = $this->router->match($userBody, $conversation);

        if ($intent === null) {
            $message = $this->fallbackReply($conversation);
            $this->conversationAi->refreshInsights($conversation->fresh());

            return $message;
        }

        $this->logs->record($conversation, 'intent_matched', [
            'intent' => $intent->slug,
            'label' => $intent->label,
        ]);

        if ($intent->requiresHandoff() || $intent->target_queue_id !== null) {
            $message = $this->handoff($conversation, $intent);
            $this->conversationAi->refreshInsights($conversation->fresh());

            return $message;
        }

        $message = $this->respondWithIntent($conversation, $intent);
        $this->conversationAi->refreshInsights($conversation->fresh());

        return $message;
    }

    private function respondWithIntent(SupportConversation $conversation, ChatbotIntent $intent): SupportMessage
    {
        if ($intent->slug === 'greeting') {
            $body = $this->menu->welcomeMessage($conversation->user);
        } else {
            $template = $intent->responses()
                ->where('locale', app()->getLocale())
                ->value('body_template')
                ?? $intent->responses()->value('body_template')
                ?? __('Obrigado pela sua mensagem. Um momento, por favor.');

            $body = $this->renderTemplate($template, $conversation);
        }

        $this->conversations->markFirstResponse($conversation);

        return $this->conversations->sendBotMessage($conversation, $body, $intent->slug);
    }

    private function handoff(SupportConversation $conversation, ChatbotIntent $intent): SupportMessage
    {
        $queue = $intent->targetQueue;

        $conversation->update([
            'support_queue_id' => $queue?->id,
            'status' => SupportConversationStatus::PendingHuman,
            'bot_active' => false,
        ]);

        $this->logs->record($conversation, 'handoff_requested', [
            'intent' => $intent->slug,
            'queue' => $queue?->slug,
        ]);

        $body = $queue
            ? __('Encaminhei o seu pedido para :queue. Protocolo :protocol. Um atendente responderá em breve.', [
                'queue' => $queue->name,
                'protocol' => $conversation->protocol_number,
            ])
            : __('Encaminhei o seu pedido para atendimento humano. Protocolo :protocol.', [
                'protocol' => $conversation->protocol_number,
            ]);

        $this->conversations->markFirstResponse($conversation);

        return $this->conversations->sendBotMessage($conversation, $body, $intent->slug, [
            'handoff' => true,
            'queue_slug' => $queue?->slug,
        ]);
    }

    private function fallbackReply(SupportConversation $conversation): SupportMessage
    {
        $this->logs->record($conversation, 'intent_fallback');

        $body = __('Não identifiquei o seu pedido. Pode reformular ou escolher uma opção abaixo.')
            ."\n\n"
            .$this->menu->formatMenu($conversation->user);

        $this->conversations->markFirstResponse($conversation);

        return $this->conversations->sendBotMessage($conversation, $body, 'fallback');
    }

    private function renderTemplate(string $template, SupportConversation $conversation): string
    {
        return str_replace(
            [':protocol', ':name'],
            [$conversation->protocol_number, $conversation->user?->name ?? ''],
            $template,
        );
    }
}
