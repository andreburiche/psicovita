<?php

namespace Tests\Feature;

use App\Enums\SupportConversationStatus;
use App\Enums\UserRole;
use App\Models\SupportConversation;
use App\Models\SupportQueue;
use App\Models\User;
use Database\Seeders\ChatbotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportDeskTest extends TestCase
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

    public function test_admin_can_view_support_desk(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.support.index'))
            ->assertOk()
            ->assertSee(__('Central de suporte'), false);
    }

    public function test_professional_cannot_access_support_desk(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($professional)
            ->get(route('admin.support.index'))
            ->assertForbidden();
    }

    public function test_admin_can_assign_reply_and_resolve_conversation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $patient = User::factory()->create(['role' => UserRole::Patient]);

        $this->actingAs($patient)->getJson(route('chatbot.widget.show'))->assertOk();
        $this->actingAs($patient)
            ->postJson(route('chatbot.widget.messages.store'), [
                'body' => 'Não recebi meu benefício',
            ])
            ->assertOk();

        $conversation = SupportConversation::query()->first();
        $this->assertSame(SupportConversationStatus::PendingHuman, $conversation->status);

        $this->actingAs($admin)
            ->post(route('admin.support.assign', $conversation))
            ->assertRedirect();

        $conversation->refresh();
        $this->assertSame($admin->id, $conversation->assigned_agent_id);
        $this->assertSame(SupportConversationStatus::Assigned, $conversation->status);

        $this->actingAs($admin)
            ->post(route('admin.support.messages.store', $conversation), [
                'body' => 'Vou verificar o seu benefício.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_messages', [
            'support_conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'sender_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.support.resolve', $conversation))
            ->assertRedirect(route('admin.support.index'));

        $this->assertSame(SupportConversationStatus::Resolved, $conversation->fresh()->status);
    }

    public function test_admin_can_transfer_conversation_to_another_queue(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $patient = User::factory()->create(['role' => UserRole::Patient]);
        $tiQueue = SupportQueue::query()->where('slug', 'ti')->first();

        $conversation = SupportConversation::query()->create([
            'user_id' => $patient->id,
            'support_queue_id' => SupportQueue::query()->where('slug', 'assistencia')->value('id'),
            'status' => SupportConversationStatus::PendingHuman,
            'protocol_number' => 'PSC-TEST-0001',
            'source_channel' => 'web_widget',
            'bot_active' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.support.transfer', $conversation), [
                'support_queue_id' => $tiQueue->id,
            ])
            ->assertRedirect();

        $conversation->refresh();
        $this->assertSame($tiQueue->id, $conversation->support_queue_id);
        $this->assertSame(SupportConversationStatus::PendingHuman, $conversation->status);
        $this->assertNull($conversation->assigned_agent_id);
    }

    public function test_patient_widget_poll_receives_agent_message(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $patient = User::factory()->create(['role' => UserRole::Patient]);

        $this->actingAs($patient)->getJson(route('chatbot.widget.show'))->assertOk();

        $conversation = SupportConversation::query()->first();
        $conversation->update([
            'status' => SupportConversationStatus::Assigned,
            'assigned_agent_id' => $admin->id,
            'bot_active' => false,
        ]);

        app(\App\Services\Chatbot\SupportDeskService::class)->sendAgentMessage(
            $conversation,
            $admin,
            'Olá, sou o atendente. Como posso ajudar?',
        );

        $lastId = $conversation->messages()->orderByDesc('id')->skip(1)->value('id') ?? 0;

        $response = $this->actingAs($patient)
            ->getJson(route('chatbot.widget.poll', ['after_id' => $lastId]))
            ->assertOk();

        $bodies = collect($response->json('messages'))->pluck('body');
        $this->assertTrue($bodies->contains('Olá, sou o atendente. Como posso ajudar?'));
    }
}
