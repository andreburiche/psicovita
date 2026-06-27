<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_patient_is_redirected_to_patient_portal_after_login(): void
    {
        $professional = User::factory()->create();
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $response = $this->post('/login', [
            'email' => $patient->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($patient);
        $response->assertRedirect(route('patient.home', absolute: false));
    }

    public function test_patient_requesting_dashboard_is_redirected_to_patient_portal(): void
    {
        $professional = User::factory()->create();
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $this->actingAs($patient)
            ->get('/dashboard')
            ->assertRedirect(route('patient.home', absolute: false))
            ->assertSessionHas('status');
    }

    public function test_misregistered_professional_with_patient_ficha_redirects_to_patient_portal_after_login(): void
    {
        $therapist = User::factory()->create(['role' => UserRole::Professional]);
        $account = User::factory()->create([
            'role' => UserRole::Professional,
            'email' => 'ficha-only@example.test',
        ]);

        Patient::factory()->create([
            'professional_id' => $therapist->id,
            'email' => 'ficha-only@example.test',
        ]);

        $response = $this->post('/login', [
            'email' => $account->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($account);
        $response->assertRedirect(route('patient.home', absolute: false));
    }

    public function test_misregistered_professional_cannot_open_dashboard(): void
    {
        $therapist = User::factory()->create(['role' => UserRole::Professional]);
        $account = User::factory()->create([
            'role' => UserRole::Professional,
            'email' => 'only-in-ficha@example.test',
        ]);

        Patient::factory()->create([
            'professional_id' => $therapist->id,
            'email' => 'only-in-ficha@example.test',
        ]);

        $this->actingAs($account)
            ->get('/dashboard')
            ->assertRedirect(route('patient.home', absolute: false));
    }

    public function test_login_accepts_email_with_different_casing_than_database(): void
    {
        $user = User::factory()->create([
            'email' => 'aburiche@gmail.com',
        ]);

        $this->post('/login', [
            'email' => 'ABURICHE@GMAIL.COM',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_logout_confirm_page_renders_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/logout');

        $response->assertOk()
            ->assertSee('data-test="confirm-logout-page"', false)
            ->assertSee('id="logout-form"', false)
            ->assertSee(__('A terminar sessão'), false)
            ->assertSee($user->email, false)
            ->assertSee(__('Cancelar e voltar à aplicação'), false);
    }

    public function test_logout_confirm_page_redirects_guests_to_login(): void
    {
        $response = $this->get('/logout');

        $response->assertRedirect(route('login'));
    }
}
