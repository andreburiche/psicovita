<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ConversationService;
use App\Services\WhatsAppConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EvolutionWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('psiconecta.whatsapp.enabled', true);
        Config::set('psiconecta.whatsapp.driver', 'evolution');
        Config::set('psiconecta.whatsapp.default_calling_code', '55');
        Config::set('psiconecta.whatsapp.evolution.api_url', 'http://evolution.test');
        Config::set('psiconecta.whatsapp.evolution.api_key', 'test-api-key');
        Config::set('psiconecta.whatsapp.evolution.instance', 'psiconecta');
    }

    public function test_evolution_gateway_is_configured(): void
    {
        $service = app(WhatsAppConversationService::class);

        $this->assertTrue($service->isConfigured());
        $this->assertSame('evolution', $service->driver());
        $this->assertSame('Evolution API', $service->driverLabel());
    }

    public function test_evolution_sends_text_message(): void
    {
        Http::fake([
            'evolution.test/message/sendText/psiconecta' => Http::response([
                'key' => ['id' => 'ABC123'],
            ], 200),
        ]);

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'phone' => '11999998888',
        ]);

        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);
        $conversation->update([
            'whatsapp_enabled' => true,
            'patient_whatsapp_consent_at' => now(),
        ]);

        $externalId = app(WhatsAppConversationService::class)->sendText($conversation, 'Olá via Evolution');

        $this->assertSame('evolution:ABC123', $externalId);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://evolution.test/message/sendText/psiconecta'
                && $request->hasHeader('apikey', 'test-api-key')
                && $request['number'] === '5511999998888'
                && $request['text'] === 'Olá via Evolution';
        });
    }

    public function test_evolution_webhook_ingests_incoming_message(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'phone' => '5511888777666',
        ]);

        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);
        $conversation->update(['whatsapp_enabled' => true]);

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'psiconecta',
            'data' => [
                'key' => [
                    'id' => 'INCOMING1',
                    'remoteJid' => '5511888777666@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'message' => [
                    'conversation' => 'Resposta do paciente',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/integrations/evolution/webhook', $payload);

        $response->assertOk()->assertJson(['ingested' => 1]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $patient->id,
            'external_id' => 'evolution:INCOMING1',
        ]);
    }

    public function test_evolution_webhook_rejects_wrong_driver(): void
    {
        Config::set('psiconecta.whatsapp.driver', 'meta');

        $this->postJson('/api/v1/integrations/evolution/webhook', [])
            ->assertStatus(503);
    }

    public function test_evolution_webhook_downloads_inbound_image_attachment(): void
    {
        $imageBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        Http::fake([
            'evolution.test/chat/getBase64FromMediaMessage/psiconecta' => Http::response([
                'base64' => base64_encode($imageBytes),
                'mimetype' => 'image/png',
                'fileName' => 'foto.png',
            ], 200),
        ]);

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'phone' => '5511777666555',
        ]);

        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);
        $conversation->update(['whatsapp_enabled' => true]);

        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'key' => [
                    'id' => 'IMG001',
                    'remoteJid' => '5511777666555@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'message' => [
                    'imageMessage' => [
                        'mimetype' => 'image/png',
                        'caption' => 'Minha foto',
                    ],
                ],
            ],
        ];

        $this->postJson('/api/v1/integrations/evolution/webhook', $payload)
            ->assertOk()
            ->assertJson(['ingested' => 1]);

        $this->assertDatabaseHas('message_attachments', [
            'original_name' => 'foto.png',
            'mime_type' => 'image/png',
        ]);
    }

    public function test_evolution_test_connection_reports_open_state(): void
    {
        Http::fake([
            'evolution.test/instance/connectionState/psiconecta' => Http::response([
                'instance' => [
                    'instanceName' => 'psiconecta',
                    'state' => 'open',
                ],
            ], 200),
        ]);

        $result = app(WhatsAppConversationService::class)->testConnection();

        $this->assertTrue($result['ok']);
        $this->assertSame('open', $result['details']['state'] ?? null);
    }
}
