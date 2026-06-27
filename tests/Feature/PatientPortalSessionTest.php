<?php

namespace Tests\Feature;

use App\Enums\TherapySessionStatus;
use App\Enums\TherapySessionType;
use App\Enums\UserRole;
use App\Enums\VideoCallStatus;
use App\Models\Patient;
use App\Models\TherapySession;
use App\Models\User;
use App\Services\SessionVideoCallService;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPortalSessionTest extends TestCase
{
    use RefreshDatabase;

    private function linkedPatientUser(): array
    {
        $email = 'paciente.video@example.com';
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'email' => $email,
            'email_hash' => ContactHasher::emailHash($email),
        ]);
        $patient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => $email,
        ]);

        return [$professional, $patientUser, $patient];
    }

    public function test_patient_sees_upcoming_online_session_in_portal(): void
    {
        [, $patientUser, $patient] = $this->linkedPatientUser();

        TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $patient->professional_id,
            'type' => TherapySessionType::Online,
            'status' => TherapySessionStatus::Scheduled,
            'session_date' => now()->toDateString(),
            'session_time' => now()->format('H:i').':00',
        ]);

        $response = $this->actingAs($patientUser)->get(route('patient.sessions.index'));

        $response->assertOk();
        $response->assertSee(__('Consultas por vídeo'), false);
        $response->assertSee(__('Aguardando o profissional abrir a sala'), false);
    }

    public function test_patient_can_join_when_room_is_live(): void
    {
        [$professional, $patientUser, $patient] = $this->linkedPatientUser();

        $session = TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'type' => TherapySessionType::Online,
            'status' => TherapySessionStatus::Scheduled,
            'session_date' => now()->toDateString(),
            'session_time' => now()->format('H:i').':00',
        ]);

        $videoCall = app(SessionVideoCallService::class)->ensureForSession($session);
        $videoCall->update(['status' => VideoCallStatus::Live, 'started_at' => now()]);

        $response = $this->actingAs($patientUser)->get(route('patient.sessions.join', $session));

        $response->assertRedirect(route('session-video.guest', ['token' => $videoCall->guest_token]));
    }

    public function test_patient_home_shows_video_sessions_widget(): void
    {
        [, $patientUser, $patient] = $this->linkedPatientUser();

        TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $patient->professional_id,
            'type' => TherapySessionType::Online,
            'status' => TherapySessionStatus::Scheduled,
            'session_date' => now()->addDay()->toDateString(),
            'session_time' => '10:00:00',
        ]);

        $response = $this->actingAs($patientUser)->get(route('patient.home'));

        $response->assertOk();
        $response->assertSee(__('Próximas consultas online'), false);
    }
}
