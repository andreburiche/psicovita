<?php

namespace App\Services\Chatbot;

use App\Enums\SupportSourceChannel;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppIncomingHandler;
use Illuminate\Support\Facades\Log;

class SupportWhatsAppHandler
{
    public function __construct(
        private readonly SupportConversationService $conversations,
        private readonly ChatbotEngineService $bot,
        private readonly ChatbotMenuService $menu,
        private readonly SupportWhatsAppOutboundService $outbound,
        private readonly ChatbotLogService $logs,
    ) {}

    public function isEnabled(): bool
    {
        return config('psiconecta.chatbot.enabled', true)
            && config('psiconecta.chatbot.whatsapp_enabled', false);
    }

    public function handle(
        string $fromDigits,
        string $body,
        string $externalId,
        string $type = 'text',
    ): bool {
        if (! $this->isEnabled()) {
            Log::warning('Support WhatsApp handler disabled', [
                'chatbot_enabled' => config('psiconecta.chatbot.enabled'),
                'whatsapp_enabled' => config('psiconecta.chatbot.whatsapp_enabled'),
            ]);

            return false;
        }

        if ($fromDigits === '' || $externalId === '') {
            return false;
        }

        if (trim($body) === '') {
            return false;
        }

        if (SupportMessage::query()->where('external_id', $externalId)->exists()) {
            return false;
        }

        $phoneDigits = WhatsAppIncomingHandler::normalizePhone($fromDigits);
        if ($phoneDigits === '') {
            return false;
        }

        $user = User::findByPhoneDigits($phoneDigits);
        $conversation = $this->conversations->ensureWhatsAppContact(
            $this->conversations->findOrCreateForWhatsApp($user, $phoneDigits),
            $phoneDigits,
        );

        $welcomeSent = (bool) data_get($conversation->bot_context, 'whatsapp_welcome_sent', false);
        if (! $welcomeSent) {
            $welcome = $this->conversations->sendBotMessage(
                $conversation,
                $this->menu->welcomeMessage($user),
                'greeting',
            );

            $this->logs->record($conversation, 'conversation_started', [
                'channel' => SupportSourceChannel::Whatsapp->value,
                'guest' => $user === null,
            ]);

            $delivered = $this->outbound->sendToPhone($phoneDigits, $welcome->body) !== null;
            $this->conversations->markWhatsAppWelcomeSent($conversation->fresh(), $delivered);
            $conversation = $conversation->fresh();
        }

        $userMessage = $this->conversations->sendIncomingMessage(
            $conversation,
            $body,
            $user,
            $externalId,
        );

        $this->logs->record($conversation, 'user_message', [
            'message_id' => $userMessage->id,
            'channel' => SupportSourceChannel::Whatsapp->value,
        ]);

        $conversation = $conversation->fresh();

        if ($conversation->bot_active) {
            $botMessage = $this->bot->reply($conversation, $body);
            if ($botMessage !== null) {
                $this->outbound->sendToPhone($phoneDigits, $botMessage->body);
            }
        } else {
            $this->outbound->sendToPhone(
                $phoneDigits,
                __('Recebemos sua mensagem. Protocolo :protocol. Um atendente responderá em breve.', [
                    'protocol' => $conversation->protocol_number,
                ]),
            );
        }

        if ($user === null) {
            Log::info('Support WhatsApp message from guest phone', [
                'from' => $phoneDigits,
                'protocol' => $conversation->protocol_number,
                'type' => $type,
            ]);
        }

        return true;
    }
}
