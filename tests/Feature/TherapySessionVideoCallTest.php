<?php

namespace Tests\Feature;

use App\Enums\TherapySessionStatus;
use App\Enums\TherapySessionType;
use App\Enums\UserRole;
use App\Enums\VideoRecordingStatus;
use App\Models\Patient;
use App\Models\TherapySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TherapySessionVideoCallTest extends TestCase
{
    use RefreshDatabase;

    private function onlineSession(User $professional, Patient $patient): TherapySession
    {
        return TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'type' => TherapySessionType::Online,
            'status' => TherapySessionStatus::Scheduled,
        ]);
    }

    public function test_professional_can_open_video_room_for_online_session(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $session = $this->onlineSession($professional, $patient);

        $response = $this->actingAs($professional)->get(route('therapy-sessions.video.room', $session));

        $response->assertOk();
        $response->assertSee(__('Sessão por vídeo'), false);
        $response->assertSee(__('Encerrar sessão e gerar devolutiva'), false);
        $this->assertDatabaseHas('therapy_session_video_calls', [
            'therapy_session_id' => $session->id,
        ]);
    }

    public function test_guest_can_join_with_token(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $session = $this->onlineSession($professional, $patient);

        $this->actingAs($professional)->get(route('therapy-sessions.video.room', $session));

        $token = $session->fresh()->videoCall->guest_token;

        $response = $this->get(route('session-video.guest', ['token' => $token]));

        $response->assertOk();
        $response->assertSee(__('Antes de entrar na sala'), false);

        $this->post(route('session-video.consent', ['token' => $token]), [
            'join_consent' => '1',
            'recording_consent' => '1',
        ])->assertRedirect(route('session-video.guest', ['token' => $token]));

        $this->get(route('session-video.guest', ['token' => $token]))
            ->assertOk()
            ->assertSee(__('Consulta online'), false);
    }

    public function test_finish_upload_triggers_ai_review_flow(): void
    {
        Storage::fake('local');
        config(['psiconecta.video_conference.recording_disk' => 'local']);
        config(['psiconecta.ai.provider' => 'mock']);

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $session = $this->onlineSession($professional, $patient);

        $this->actingAs($professional)->get(route('therapy-sessions.video.room', $session));

        $file = UploadedFile::fake()->create('sessao.mp3', 120, 'audio/mpeg');

        $response = $this->actingAs($professional)->post(route('therapy-sessions.video.finish', $session), [
            'recording' => $file,
            'approach' => 'tcc',
            'lgpd_recording_consent' => '1',
        ]);

        $response->assertRedirect(route('therapy-sessions.video.review', $session));

        $videoCall = $session->fresh()->videoCall;
        $this->assertSame(VideoRecordingStatus::Completed, $videoCall->recording_status);
        $this->assertNotNull($videoCall->transcription_text);
        $this->assertNotNull($videoCall->devolutiva_patient_text);

        $review = $this->actingAs($professional)->get(route('therapy-sessions.video.review', $session));
        $review->assertOk();
        $review->assertSee(__('Devolutiva ao paciente'), false);
    }

    public function test_session_show_lists_video_action_for_online_sessions(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $session = $this->onlineSession($professional, $patient);

        $response = $this->actingAs($professional)->get(route('therapy-sessions.show', $session));

        $response->assertOk();
        $response->assertSee(__('Iniciar videoconferência'), false);
    }

    public function test_session_show_lists_video_for_in_person_sessions(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $session = TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'type' => TherapySessionType::InPerson,
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $response = $this->actingAs($professional)->get(route('therapy-sessions.show', $session));

        $response->assertOk();
        $response->assertSee(__('Videoconferência'), false);
    }
}
