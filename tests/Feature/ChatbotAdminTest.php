<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ChatbotIntent;
use App\Models\SupportConversation;
use App\Models\User;
use Database\Seeders\ChatbotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['psiconecta.chatbot.enabled' => true]);
        $this->seed(ChatbotSeeder::class);
    }

    public function test_admin_can_view_chatbot_metrics_dashboard(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.support.metrics'))
            ->assertOk()
            ->assertSee(__('Métricas do chatbot'), false);
    }

    public function test_admin_can_view_and_create_intent(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.chatbot.intents.index'))
            ->assertOk()
            ->assertSee('benefit_issue', false);

        $this->actingAs($admin)
            ->post(route('admin.chatbot.intents.store'), [
                'label' => 'Horário de funcionamento',
                'slug' => 'business_hours',
                'training_phrases' => "horario\nhorário de atendimento",
                'route_action' => 'reply',
                'target_queue_id' => null,
                'priority' => 40,
                'body_template' => 'Atendemos de segunda a sexta, 9h às 18h.',
                'quick_replies' => 'Falar com atendente',
            ])
            ->assertRedirect(route('admin.chatbot.intents.index'));

        $this->assertDatabaseHas('chatbot_intents', [
            'slug' => 'business_hours',
            'route_action' => 'reply',
        ]);
    }

    public function test_admin_can_update_intent(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $intent = ChatbotIntent::query()->where('slug', 'greeting')->first();
        $this->assertNotNull($intent);

        $this->actingAs($admin)
            ->patch(route('admin.chatbot.intents.update', $intent), [
                'label' => 'Saudação actualizada',
                'slug' => $intent->slug,
                'training_phrases' => "ola\noi",
                'route_action' => 'reply',
                'priority' => 15,
                'body_template' => 'Olá! Em que posso ajudar?',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.chatbot.intents.index'));

        $this->assertSame('Saudação actualizada', $intent->fresh()->label);
    }

    public function test_metrics_reflect_conversations(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $patient = User::factory()->create(['role' => UserRole::Patient]);

        $this->actingAs($patient)->getJson(route('chatbot.widget.show'))->assertOk();
        $this->actingAs($patient)
            ->postJson(route('chatbot.widget.messages.store'), ['body' => 'Não recebi meu benefício'])
            ->assertOk();

        $this->assertSame(1, SupportConversation::query()->count());

        $this->actingAs($admin)
            ->get(route('admin.support.metrics', ['days' => 30]))
            ->assertOk()
            ->assertSee('1', false);
    }
}
