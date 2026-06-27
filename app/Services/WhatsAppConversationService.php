<?php

namespace App\Services;

use App\Contracts\WhatsAppGatewayInterface;
use App\Models\Conversation;
use App\Models\MessageAttachment;
use App\Services\WhatsApp\WhatsAppGatewayFactory;
use App\Services\WhatsApp\WhatsAppIncomingHandler;

class WhatsAppConversationService
{
    private WhatsAppGatewayInterface $gateway;

    public function __construct(
        private readonly WhatsAppGatewayFactory $factory,
    ) {
        $this->gateway = $this->factory->make();
    }

    public function driver(): string
    {
        return $this->gateway->driver();
    }

    public function driverLabel(): string
    {
        return $this->factory->driverLabel();
    }

    public function isConfigured(): bool
    {
        return $this->gateway->isConfigured();
    }

    public function sendText(Conversation $conversation, string $body): ?string
    {
        if (! $this->isConfigured() || ! $conversation->canSyncWhatsApp()) {
            return null;
        }

        $phone = $this->resolveE164Phone($conversation);
        if ($phone === null) {
            return null;
        }

        return $this->gateway->sendText($phone, $body);
    }

    public function patientPhoneDigits(Conversation $conversation): ?string
    {
        return $this->resolveE164Phone($conversation);
    }

    public function canSendToPatient(Conversation $conversation): bool
    {
        return $this->isConfigured()
            && $conversation->canSyncWhatsApp()
            && $this->resolveE164Phone($conversation) !== null;
    }

    public function sendDocument(Conversation $conversation, MessageAttachment $attachment, ?string $caption = null): ?string
    {
        if (! $this->isConfigured() || ! $conversation->canSyncWhatsApp()) {
            return null;
        }

        $phone = $this->resolveE164Phone($conversation);
        if ($phone === null) {
            return null;
        }

        return $this->gateway->sendDocument($phone, $attachment, $caption);
    }

    public function sendSessionTemplate(Conversation $conversation, string $patientName): ?string
    {
        if (! $this->isConfigured() || ! $conversation->canSyncWhatsApp()) {
            return null;
        }

        $phone = $this->resolveE164Phone($conversation);
        if ($phone === null) {
            return null;
        }

        return $this->gateway->sendSessionTemplate($phone, $patientName);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingestWebhookPayload(array $payload): int
    {
        if (! $this->isConfigured()) {
            return 0;
        }

        return $this->gateway->ingestWebhookPayload($payload);
    }

    /**
     * @return array{ok: bool, message: string, details?: array<string, mixed>}
     */
    public function testConnection(): array
    {
        return $this->gateway->testConnection();
    }

    private function resolveE164Phone(Conversation $conversation): ?string
    {
        $patient = $conversation->patient;
        $phone = $patient?->phone ?: $conversation->patientUser?->phone;

        if (! $phone) {
            return null;
        }

        return WhatsAppIncomingHandler::normalizePhone((string) $phone);
    }
}
