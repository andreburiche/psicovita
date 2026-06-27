<?php

namespace Tests\Feature;

use App\Enums\SupportConversationStatus;
use App\Models\User;
use Database\Seeders\ChatbotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'psiconecta.chatbot.enabled' => true,
            'psiconecta.chatbot.widget_enabled' => true,
        ]);

        $this->seed(ChatbotSeeder::class);
    }

    public function test_user_can_open_chatbot_widget_and_receive_greeting(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('chatbot.widget.show'))
            ->assertOk()
            ->assertJsonPath('conversation.protocol_number', fn ($value) => is_string($value) && str_starts_with($value, 'PSC-'))
            ->assertJsonCount(1, 'messages');
    }

    public function test_user_message_triggers_intent_handoff(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('chatbot.widget.show'))->assertOk();

        $response = $this->actingAs($user)
            ->postJson(route('chatbot.widget.messages.store'), [
                'body' => 'Não recebi meu benefício',
            ])
            ->assertOk();

        $response->assertJsonPath('conversation.status', SupportConversationStatus::PendingHuman->value);
        $response->assertJsonPath('conversation.queue.slug', 'assistencia');

        $bot = collect($response->json('messages'))->firstWhere('sender_type', 'bot');
        $this->assertNotNull($bot);
        $this->assertStringContainsString('Assistência Social', $bot['body']);
    }
}
