<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertOk()->assertSee('data-test="landing-hero"', false);
    }

    public function test_home_redirects_authenticated_professional_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_home_redirects_authenticated_patient_to_patient_portal(): void
    {
        $professional = User::factory()->create();
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $this->actingAs($patient)->get('/')->assertRedirect(route('patient.home'));
    }
}
