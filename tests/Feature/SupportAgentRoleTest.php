<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\ChatbotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportAgentRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['psiconecta.chatbot.enabled' => true]);
        $this->seed(ChatbotSeeder::class);
    }

    public function test_support_agent_can_access_support_desk(): void
    {
        $agent = User::factory()->create(['role' => UserRole::SupportAgent]);

        $this->actingAs($agent)
            ->get(route('admin.support.index'))
            ->assertOk()
            ->assertSee(__('Central de suporte'), false);
    }

    public function test_support_agent_cannot_access_admin_metrics_or_intents(): void
    {
        $agent = User::factory()->create(['role' => UserRole::SupportAgent]);

        $this->actingAs($agent)
            ->get(route('admin.support.metrics'))
            ->assertForbidden();

        $this->actingAs($agent)
            ->get(route('admin.chatbot.intents.index'))
            ->assertForbidden();
    }

    public function test_support_agent_login_redirects_to_support_desk(): void
    {
        $agent = User::factory()->create(['role' => UserRole::SupportAgent]);

        $this->assertSame('admin.support.index', $agent->defaultAppRouteName());
    }
}
