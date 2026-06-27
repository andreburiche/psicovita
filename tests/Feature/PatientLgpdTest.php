<?php

namespace Tests\Feature;

use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use App\Enums\UserRole;
use App\Mail\DataSubjectRequestNotificationMail;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PatientLgpdTest extends TestCase
{
    use RefreshDatabase;

    private function patientUserWithFicha(): array
    {
        $therapist = User::factory()->create(['role' => UserRole::Professional]);
        $email = 'paciente.lgpd@example.test';

        Patient::factory()->create([
            'professional_id' => $therapist->id,
            'email' => $email,
            'name' => 'Paciente LGPD',
        ]);

        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'email' => $email,
            'professional_id' => $therapist->id,
        ]);

        return [$patientUser, $therapist];
    }

    public function test_lgpd_portal_requires_patient_experience(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($professional)
            ->get(route('patient.lgpd.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_patient_can_view_lgpd_portal(): void
    {
        [$patientUser] = $this->patientUserWithFicha();

        $this->actingAs($patientUser)
            ->get(route('patient.lgpd.index'))
            ->assertOk()
            ->assertSee(__('Privacidade e direitos LGPD'), false)
            ->assertSee(__('Exportar meus dados (JSON)'), false);
    }

    public function test_patient_can_submit_lgpd_request(): void
    {
        Mail::fake();
        [$patientUser] = $this->patientUserWithFicha();

        $response = $this->actingAs($patientUser)->post(route('patient.lgpd.store'), [
            'type' => DataSubjectRequestType::Access->value,
            'details' => 'Gostaria de confirmar quais dados são tratados.',
        ]);

        $response->assertRedirect(route('patient.lgpd.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('data_subject_requests', [
            'user_id' => $patientUser->id,
            'type' => DataSubjectRequestType::Access->value,
            'status' => DataSubjectRequestStatus::Pending->value,
        ]);

        Mail::assertSent(DataSubjectRequestNotificationMail::class);
    }

    public function test_patient_can_export_personal_data_json(): void
    {
        [$patientUser] = $this->patientUserWithFicha();

        $response = $this->actingAs($patientUser)->get(route('patient.lgpd.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json; charset=UTF-8');

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('1.0', $payload['schema_version']);
        $this->assertSame($patientUser->email, $payload['account']['email']);
        $this->assertCount(1, $payload['patients']);
        $this->assertSame('Paciente LGPD', $payload['patients'][0]['profile']['name']);
    }

    public function test_lgpd_request_requires_type(): void
    {
        [$patientUser] = $this->patientUserWithFicha();

        $this->actingAs($patientUser)
            ->post(route('patient.lgpd.store'), [])
            ->assertSessionHasErrors('type');
    }

    public function test_patient_can_export_personal_data_pdf(): void
    {
        [$patientUser] = $this->patientUserWithFicha();

        $response = $this->actingAs($patientUser)->get(route('patient.lgpd.export.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }
}
