<?php

namespace App\Services\Chatbot;

use App\Models\SupportConversation;
use App\Services\WhatsApp\WhatsAppGatewayFactory;
use App\Services\WhatsApp\WhatsAppIncomingHandler;
use Illuminate\Support\Facades\Log;

class SupportWhatsAppOutboundService
{
    public function __construct(
        private readonly WhatsAppGatewayFactory $gatewayFactory,
    ) {}

    public function isAvailable(): bool
    {
        return config('psiconecta.whatsapp.enabled', false)
            && $this->gatewayFactory->make()->isConfigured();
    }

    public function sendToConversation(
        SupportConversation $conversation,
        string $body,
        ?string $phoneDigits = null,
    ): ?string {
        $phone = $phoneDigits ?? $this->resolvePhoneDigits($conversation);
        if ($phone === null) {
            Log::warning('Support WhatsApp outbound skipped: phone not resolved', [
                'conversation_id' => $conversation->id,
                'protocol' => $conversation->protocol_number,
            ]);

            return null;
        }

        return $this->sendToPhone($phone, $body);
    }

    public function sendToPhone(string $phoneDigits, string $body): ?string
    {
        $body = trim($body);
        if ($body === '') {
            Log::warning('Support WhatsApp outbound skipped: empty body');

            return null;
        }

        if (! $this->isAvailable()) {
            Log::warning('Support WhatsApp outbound skipped: integration unavailable', [
                'whatsapp_enabled' => config('psiconecta.whatsapp.enabled'),
                'driver' => config('psiconecta.whatsapp.driver'),
            ]);

            return null;
        }

        $phone = WhatsAppIncomingHandler::normalizePhone($phoneDigits);
        if ($phone === '') {
            Log::warning('Support WhatsApp outbound skipped: invalid phone', [
                'phone' => $phoneDigits,
            ]);

            return null;
        }

        $externalId = $this->gatewayFactory->make()->sendText($phone, $body);

        if ($externalId === null) {
            Log::warning('Support WhatsApp outbound failed', [
                'phone' => $phone,
                'body_length' => strlen($body),
            ]);
        } else {
            Log::info('Support WhatsApp outbound sent', [
                'phone' => $phone,
                'external_id' => $externalId,
            ]);
        }

        return $externalId;
    }

    private function resolvePhoneDigits(SupportConversation $conversation): ?string
    {
        $fromContext = data_get($conversation->bot_context, 'whatsapp_phone');
        if (is_string($fromContext) && $fromContext !== '') {
            $digits = WhatsAppIncomingHandler::normalizePhone($fromContext);

            return $digits !== '' ? $digits : null;
        }

        $userPhone = $conversation->user?->phone;
        if ($userPhone) {
            $digits = WhatsAppIncomingHandler::normalizePhone((string) $userPhone);

            return $digits !== '' ? $digits : null;
        }

        return null;
    }
}
