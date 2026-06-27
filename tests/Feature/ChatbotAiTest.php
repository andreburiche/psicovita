<?php

namespace Tests\Feature;

use App\Enums\SupportConversationStatus;
use App\Models\SupportConversation;
use App\Models\User;
use Database\Seeders\ChatbotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotAiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'psiconecta.chatbot.enabled' => true,
            'psiconecta.chatbot.widget_enabled' => true,
            'psiconecta.chatbot.ai_enabled' => true,
            'psiconecta.ai.enabled' => true,
            'psiconecta.ai.provider' => 'openai',
            'psiconecta.ai.openai_api_key' => 'test-key',
            'psiconecta.ai.openai_base_url' => 'https://api.openai.com/v1',
        ]);

        $this->seed(ChatbotSeeder::class);
    }

    public function test_phrase_match_takes_priority_without_calling_ai(): void
    {
        config(['psiconecta.chatbot.ai_enabled' => false]);

        Http::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('chatbot.widget.show'))->assertOk();

        $this->actingAs($user)
            ->postJson(route('chatbot.widget.messages.store'), [
                'body' => 'Não recebi meu benefício',
            ])
            ->assertOk()
            ->assertJsonPath('conversation.status', SupportConversationStatus::PendingHuman->value);

        Http::assertNothingSent();
    }

    public function test_ai_classifies_message_when_phrase_match_fails(): void
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::sequence()
                ->push($this->llmJsonResponse([
                    'intent_slug' => 'benefit_issue',
                    'confidence' => 0.91,
                ]))
                ->push($this->llmJsonResponse([
                    'summary' => 'Utilizador reporta atraso no subsídio.',
                    'sentiment_score' => -0.4,
                ])),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('chatbot.widget.show'))->assertOk();

        $this->actingAs($user)
            ->postJson(route('chatbot.widget.messages.store'), [
                'body' => 'O subsídio deste mês ainda não entrou na conta',
            ])
            ->assertOk()
            ->assertJsonPath('conversation.status', SupportConversationStatus::PendingHuman->value)
            ->assertJsonPath('conversation.queue.slug', 'assistencia');

        $this->assertDatabaseHas('chatbot_logs', [
            'event' => 'intent_ai_matched',
        ]);

        Http::assertSentCount(2);
    }

    public function test_ai_refreshes_summary_and_sentiment_after_handoff(): void
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response(
                $this->llmJsonResponse([
                    'summary' => 'Paciente insatisfeito com atraso de benefício.',
                    'sentiment_score' => -0.55,
                ]),
                200,
            ),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('chatbot.widget.show'))->assertOk();

        $this->actingAs($user)
            ->postJson(route('chatbot.widget.messages.store'), [
                'body' => 'Não recebi meu benefício',
            ])
            ->assertOk();

        $conversation = SupportConversation::query()->first();
        $this->assertNotNull($conversation);
        $this->assertNotNull($conversation->ai_summary);
        $this->assertSame('-0.550', number_format((float) $conversation->sentiment_score, 3, '.', ''));

        $this->assertDatabaseHas('chatbot_logs', [
            'support_conversation_id' => $conversation->id,
            'event' => 'ai_insights_refreshed',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function llmJsonResponse(array $payload): array
    {
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    ],
                ],
            ],
            'usage' => ['total_tokens' => 42],
        ];
    }
}
