<?php

namespace Tests\Feature;

use App\Enums\UserProfessionalFunction;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_patient_profile_uses_distinct_patient_shell(): void
    {
        $professional = User::factory()->create();
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $this->actingAs($patient)
            ->get('/profile')
            ->assertOk()
            ->assertSee(__('Início'), false)
            ->assertDontSee(__('Área clínica'), false);

        $this->actingAs($patient)
            ->get(route('patient.home'))
            ->assertOk()
            ->assertSee(__('Mensagens'), false)
            ->assertDontSee(__('Área clínica'), false);
    }

    public function test_misregistered_professional_with_ficha_email_sees_patient_shell(): void
    {
        $therapist = User::factory()->create(['role' => UserRole::Professional]);
        $wrongRoleAccount = User::factory()->create([
            'role' => UserRole::Professional,
            'email' => 'paciente-ficha@example.test',
        ]);

        Patient::factory()->create([
            'professional_id' => $therapist->id,
            'email' => 'paciente-ficha@example.test',
        ]);

        $this->actingAs($wrongRoleAccount)
            ->get('/profile')
            ->assertOk()
            ->assertSee(__('Privacidade'), false);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'professional_function' => UserProfessionalFunction::Psychologist->value,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_professional_can_save_phone_on_profile(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '(11) 98765-4321',
                'professional_function' => $user->professional_function->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('11987654321', $user->fresh()->phone);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'professional_function' => $user->professional_function?->value ?? UserProfessionalFunction::Psychologist->value,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_professional_with_patients_must_acknowledge_data_loss(): void
    {
        $user = User::factory()->create(['role' => UserRole::Professional]);
        Patient::factory()->create(['professional_id' => $user->id]);

        $this->actingAs($user)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'acknowledge_data_loss');

        $this->assertNotNull($user->fresh());
    }

    public function test_professional_with_patients_can_delete_after_acknowledgement(): void
    {
        $user = User::factory()->create(['role' => UserRole::Professional]);
        Patient::factory()->create(['professional_id' => $user->id]);

        $this->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
                'acknowledge_data_loss' => '1',
            ])
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
    }
}
