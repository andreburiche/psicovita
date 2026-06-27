<?php

namespace Tests\Feature\Auth;

use App\Enums\UserProfessionalFunction;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => '1',
            'professional_function' => UserProfessionalFunction::Psychologist->value,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_normalizes_email_to_lowercase(): void
    {
        $this->post('/register', [
            'name' => 'Aburiche User',
            'email' => 'ABURICHE@GMAIL.COM',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => '1',
            'professional_function' => UserProfessionalFunction::Psychotherapist->value,
        ]);

        $this->assertDatabaseHas('users', [
            'email_hash' => ContactHasher::emailHash('aburiche@gmail.com'),
        ]);
    }

    public function test_registration_creates_patient_when_email_matches_single_practice_patient_file(): void
    {
        $therapist = User::factory()->create(['role' => UserRole::Professional]);

        Patient::factory()->create([
            'professional_id' => $therapist->id,
            'email' => 'novo.paciente@example.test',
            'name' => 'Paciente Ficha',
        ]);

        $response = $this->post('/register', [
            'name' => 'Paciente Conta',
            'email' => 'NOVO.PACIENTE@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('patient.home', absolute: false));

        $this->assertDatabaseHas('users', [
            'email_hash' => ContactHasher::emailHash('novo.paciente@example.test'),
            'role' => UserRole::Patient->value,
            'professional_id' => $therapist->id,
        ]);
    }

    public function test_registration_requires_professional_function_for_new_professionals(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sem Função',
            'email' => 'sem.funcao@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => '1',
        ]);

        $response->assertSessionHasErrors('professional_function');
    }

    public function test_registration_stays_professional_when_email_on_patient_files_of_multiple_practices(): void
    {
        $therapistA = User::factory()->create(['role' => UserRole::Professional]);
        $therapistB = User::factory()->create(['role' => UserRole::Professional]);

        Patient::factory()->create([
            'professional_id' => $therapistA->id,
            'email' => 'partilhado@example.test',
        ]);
        Patient::factory()->create([
            'professional_id' => $therapistB->id,
            'email' => 'partilhado@example.test',
        ]);

        $response = $this->post('/register', [
            'name' => 'Utilizador',
            'email' => 'partilhado@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => '1',
            'professional_function' => UserProfessionalFunction::ClinicalPsychologist->value,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'email_hash' => ContactHasher::emailHash('partilhado@example.test'),
            'role' => UserRole::Professional->value,
            'professional_id' => null,
        ]);
    }
}
