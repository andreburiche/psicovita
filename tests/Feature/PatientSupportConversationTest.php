<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\ChatbotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientSupportConversationTest extends TestCase
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

    public function test_patient_can_view_support_tab_page(): void
    {
        $patient = User::factory()->create(['role' => UserRole::Patient]);

        $this->actingAs($patient)
            ->get(route('conversations.support.index'))
            ->assertOk()
            ->assertSee(__('Apoio PsiConecta'), false)
            ->assertSee('PSC-', false);
    }

    public function test_patient_can_send_message_via_support_page(): void
    {
        $patient = User::factory()->create(['role' => UserRole::Patient]);

        $this->actingAs($patient)->get(route('conversations.support.index'))->assertOk();

        $this->actingAs($patient)
            ->postJson(route('conversations.support.messages.store'), [
                'body' => 'Preciso atualizar meu cadastro',
            ])
            ->assertOk()
            ->assertJsonPath('conversation.protocol_number', fn ($v) => is_string($v) && str_starts_with($v, 'PSC-'));
    }

    public function test_conversations_index_shows_terapia_and_apoio_tabs_for_patient(): void
    {
        $patient = User::factory()->create(['role' => UserRole::Patient]);

        $this->actingAs($patient)
            ->get(route('conversations.index'))
            ->assertOk()
            ->assertSee(__('Terapia'), false)
            ->assertSee(__('Apoio'), false);
    }
}
