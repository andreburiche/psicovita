<?php

namespace Tests\Feature;

use App\Enums\SupportConversationStatus;
use App\Enums\SupportSourceChannel;
use App\Enums\UserRole;
use App\Models\SupportConversation;
use App\Models\User;
use App\Support\ContactHasher;
use Database\Seeders\ChatbotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SupportWhatsAppTest extends TestCase
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
        Config::set('psiconecta.chatbot.enabled', true);
        Config::set('psiconecta.chatbot.whatsapp_enabled', true);

        $this->seed(ChatbotSeeder::class);
    }

    public function test_support_whatsapp_creates_conversation_and_replies(): void
    {
        Http::fake([
            'evolution.test/message/sendText/psiconecta' => Http::response([
                'key' => ['id' => 'BOT001'],
            ], 200),
        ]);

        User::factory()->create([
            'role' => UserRole::Patient,
            'phone' => '5511999001122',
        ]);

        $payload = $this->incomingPayload('SUP001', '5511999001122', 'Olá, preciso de ajuda');

        $this->postJson('/api/v1/integrations/evolution/webhook', $payload)
            ->assertOk()
            ->assertJson(['ingested' => 1]);

        $conversation = SupportConversation::query()->first();
        $this->assertNotNull($conversation);
        $this->assertSame(SupportSourceChannel::Whatsapp, $conversation->source_channel);
        $this->assertNotNull($conversation->whatsapp_phone_hash);

        $this->assertDatabaseHas('support_messages', [
            'support_conversation_id' => $conversation->id,
            'external_id' => 'evolution:SUP001',
        ]);

        Http::assertSentCount(2);
    }

    public function test_support_whatsapp_routes_benefit_intent_to_queue(): void
    {
        Http::fake([
            'evolution.test/message/sendText/psiconecta' => Http::response([
                'key' => ['id' => 'BOT002'],
            ], 200),
        ]);

        User::factory()->create([
            'role' => UserRole::Patient,
            'phone' => '5511888002233',
        ]);

        $payload = $this->incomingPayload('SUP002', '5511888002233', 'Não recebi meu benefício');

        $this->postJson('/api/v1/integrations/evolution/webhook', $payload)
            ->assertOk()
            ->assertJson(['ingested' => 1]);

        $conversation = SupportConversation::query()->first();
        $this->assertSame(SupportConversationStatus::PendingHuman, $conversation->status);
        $this->assertSame('assistencia', $conversation->queue?->slug);
    }

    public function test_clinical_whatsapp_takes_priority_over_support_chatbot(): void
    {
        Http::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'phone' => '5511777003344',
        ]);

        $clinical = app(\App\Services\ConversationService::class)->findOrCreateForUsers($professional, $patient);
        $clinical->update(['whatsapp_enabled' => true]);

        $payload = $this->incomingPayload('CLIN001', '5511777003344', 'Mensagem clínica');

        $this->postJson('/api/v1/integrations/evolution/webhook', $payload)
            ->assertOk()
            ->assertJson(['ingested' => 1]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $clinical->id,
            'external_id' => 'evolution:CLIN001',
        ]);

        $this->assertDatabaseCount('support_conversations', 0);
        Http::assertNothingSent();
    }

    public function test_unknown_phone_starts_guest_conversation_with_menu(): void
    {
        Http::fake([
            'evolution.test/message/sendText/psiconecta' => Http::response([
                'key' => ['id' => 'GUEST001'],
            ], 200),
        ]);

        $payload = $this->incomingPayload('GUEST001', '5511666004455', 'olá');

        $this->postJson('/api/v1/integrations/evolution/webhook', $payload)
            ->assertOk()
            ->assertJson(['ingested' => 1]);

        $conversation = SupportConversation::query()->first();
        $this->assertNotNull($conversation);
        $this->assertNull($conversation->user_id);
        $this->assertSame(SupportSourceChannel::Whatsapp, $conversation->source_channel);

        Http::assertSent(function ($request) {
            $text = (string) data_get($request->data(), 'text', '');

            return str_contains($text, 'Escolha uma opção')
                && str_contains($text, 'Agendar atendimento');
        });
    }

    public function test_guest_can_request_human_agent_by_menu_number(): void
    {
        Http::fake([
            'evolution.test/message/sendText/psiconecta' => Http::response([
                'key' => ['id' => 'GUEST002'],
            ], 200),
        ]);

        $this->postJson('/api/v1/integrations/evolution/webhook', $this->incomingPayload('GUEST002A', '5511666004456', 'oi'))
            ->assertJson(['ingested' => 1]);

        $this->postJson('/api/v1/integrations/evolution/webhook', $this->incomingPayload('GUEST002B', '5511666004456', '2'))
            ->assertJson(['ingested' => 1]);

        $conversation = SupportConversation::query()->first();
        $this->assertSame(SupportConversationStatus::PendingHuman, $conversation->status);
        $this->assertSame('admin', $conversation->queue?->slug);
    }

    public function test_registered_user_receives_options_menu_on_greeting(): void
    {
        Http::fake([
            'evolution.test/message/sendText/psiconecta' => Http::response([
                'key' => ['id' => 'MENU001'],
            ], 200),
        ]);

        User::factory()->create([
            'role' => UserRole::Patient,
            'phone' => '5511444007788',
        ]);

        $this->postJson('/api/v1/integrations/evolution/webhook', $this->incomingPayload('MENU001', '5511444007788', 'olá'))
            ->assertJson(['ingested' => 1]);

        Http::assertSent(function ($request) {
            $text = (string) data_get($request->data(), 'text', '');

            return str_contains($text, 'Escolha uma opção')
                && str_contains($text, 'Agendar atendimento');
        });
    }

    public function test_support_whatsapp_deduplicates_external_id(): void
    {
        Http::fake([
            'evolution.test/message/sendText/psiconecta' => Http::response([
                'key' => ['id' => 'BOT003'],
            ], 200),
        ]);

        User::factory()->create([
            'role' => UserRole::Patient,
            'phone' => '5511555005566',
        ]);

        $payload = $this->incomingPayload('DUP001', '5511555005566', 'Primeira mensagem');

        $this->postJson('/api/v1/integrations/evolution/webhook', $payload)->assertJson(['ingested' => 1]);
        $countAfterFirst = SupportConversation::query()->first()?->messages()->count();
        $this->postJson('/api/v1/integrations/evolution/webhook', $payload)->assertJson(['ingested' => 0]);

        $this->assertSame($countAfterFirst, SupportConversation::query()->first()?->messages()->count());
    }

    public function test_support_whatsapp_handles_rio_phone_number(): void
    {
        Http::fake([
            'evolution.test/message/sendText/psiconecta' => Http::response([
                'key' => ['id' => 'RIO001'],
            ], 200),
        ]);

        User::factory()->create([
            'role' => \App\Enums\UserRole::Patient,
            'phone' => '21987874549',
        ]);

        $payload = $this->incomingPayload('RIO001', '5521987874549', 'olá');

        $this->postJson('/api/v1/integrations/evolution/webhook', $payload)
            ->assertOk()
            ->assertJson(['ingested' => 1]);

        $this->assertDatabaseHas('support_conversations', [
            'source_channel' => 'whatsapp',
        ]);
    }

    public function test_assigned_conversation_still_receives_whatsapp_acknowledgment(): void
    {
        Http::fake([
            'evolution.test/message/sendText/psiconecta' => Http::response([
                'key' => ['id' => 'ACK001'],
            ], 200),
        ]);

        $conversation = SupportConversation::query()->create([
            'user_id' => null,
            'status' => SupportConversationStatus::Assigned,
            'protocol_number' => 'PSC-TEST-ACK',
            'source_channel' => SupportSourceChannel::Whatsapp,
            'whatsapp_phone_hash' => ContactHasher::phoneHash('5511333001122'),
            'bot_context' => [
                'whatsapp_phone' => '5511333001122',
                'whatsapp_welcome_sent' => true,
            ],
            'bot_active' => false,
            'assigned_agent_id' => User::factory()->create(['role' => UserRole::Professional])->id,
        ]);

        $payload = $this->incomingPayload('ACK001', '5511333001122', 'ainda preciso de ajuda');

        $this->postJson('/api/v1/integrations/evolution/webhook', $payload)
            ->assertOk()
            ->assertJson(['ingested' => 1]);

        Http::assertSent(function ($request) {
            $text = (string) data_get($request->data(), 'text', '');

            return str_contains($text, 'Recebemos sua mensagem')
                && str_contains($text, 'PSC-TEST-ACK');
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function incomingPayload(string $messageId, string $phone, string $body): array
    {
        return [
            'event' => 'messages.upsert',
            'instance' => 'psiconecta',
            'data' => [
                'key' => [
                    'id' => $messageId,
                    'remoteJid' => $phone.'@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'message' => [
                    'conversation' => $body,
                ],
            ],
        ];
    }
}
