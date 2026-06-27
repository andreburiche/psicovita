<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiSanctumTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_bearer_token_for_professional(): void
    {
        $user = User::factory()->create([
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'abilities', 'user' => ['id', 'name', 'email', 'role']])
            ->assertJsonPath('abilities', ['*']);
    }

    public function test_login_accepts_optional_abilities(): void
    {
        $user = User::factory()->create([
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'abilities' => ['api:read'],
        ]);

        $response->assertOk()
            ->assertJsonPath('abilities', ['api:read']);
    }

    public function test_token_with_read_only_cannot_create_patient(): void
    {
        $user = User::factory()->create([
            'password' => 'secret123',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'abilities' => ['api:read'],
        ])->json('token');

        $this->withToken($token)
            ->postJson('/api/v1/patients', ['name' => 'Novo'])
            ->assertForbidden();
    }

    public function test_authenticated_professional_can_list_patients_via_api(): void
    {
        $user = User::factory()->create();
        Patient::factory()->count(2)->create(['professional_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/patients')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
