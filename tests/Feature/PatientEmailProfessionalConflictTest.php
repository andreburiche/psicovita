<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientEmailProfessionalConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_form_rejects_email_used_by_professional_account(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        User::factory()->create([
            'role' => UserRole::Professional,
            'email' => 'conflito@example.com',
            'name' => 'Conta Profissional',
        ]);

        $this->actingAs($professional)
            ->post(route('patients.store'), [
                'name' => 'Paciente Novo',
                'email' => 'conflito@example.com',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_reassign_command_converts_misregistered_professional_to_patient(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional, 'id' => 50]);

        $patientRecord = Patient::factory()->create([
            'professional_id' => $professional->id,
            'name' => 'Livia Estagne',
            'email' => 'livia@example.com',
        ]);

        $wrongUser = User::factory()->create([
            'role' => UserRole::Professional,
            'email' => 'livia@example.com',
            'name' => 'teste',
        ]);

        $this->artisan('patients:reassign-login-email', ['email' => 'livia@example.com'])
            ->assertSuccessful();

        $wrongUser->refresh();
        $this->assertTrue($wrongUser->isPatient());
        $this->assertSame('Livia Estagne', $wrongUser->name);
        $this->assertSame($professional->id, $wrongUser->professional_id);
        $this->assertSame(
            ContactHasher::emailHash('livia@example.com'),
            $wrongUser->email_hash,
        );
    }
}
