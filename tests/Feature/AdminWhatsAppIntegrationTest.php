<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminWhatsAppIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('psiconecta.whatsapp.enabled', true);
        Config::set('psiconecta.whatsapp.driver', 'evolution');
        Config::set('psiconecta.whatsapp.evolution.api_url', 'http://evolution.test');
        Config::set('psiconecta.whatsapp.evolution.api_key', 'test-api-key');
        Config::set('psiconecta.whatsapp.evolution.instance', 'psiconecta');
    }

    public function test_non_admin_cannot_access_whatsapp_integration_page(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($professional)
            ->get(route('admin.integrations.whatsapp'))
            ->assertForbidden();
    }

    public function test_admin_can_view_whatsapp_integration_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.integrations.whatsapp'))
            ->assertOk()
            ->assertSee('Evolution API')
            ->assertSee('MESSAGES_UPSERT');
    }

    public function test_admin_can_test_evolution_connection(): void
    {
        Http::fake([
            'evolution.test/instance/connectionState/psiconecta' => Http::response([
                'instance' => ['state' => 'open', 'instanceName' => 'psiconecta'],
            ], 200),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('admin.integrations.whatsapp.test'))
            ->assertRedirect(route('admin.integrations.whatsapp'))
            ->assertSessionHas('connection_ok');
    }
}
