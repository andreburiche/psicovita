<?php

namespace App\Contracts;

use App\Models\MessageAttachment;

interface WhatsAppGatewayInterface
{
    public function driver(): string;

    public function isConfigured(): bool;

    public function sendText(string $phone, string $body): ?string;

    public function sendDocument(string $phone, MessageAttachment $attachment, ?string $caption = null): ?string;

    public function sendSessionTemplate(string $phone, string $patientName): ?string;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingestWebhookPayload(array $payload): int;

    /**
     * @return array{ok: bool, message: string, details?: array<string, mixed>}
     */
    public function testConnection(): array;
}
