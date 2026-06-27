<?php

namespace Tests\Feature;

use App\Enums\SessionMode;
use App\Enums\SessionParticipantRole;
use App\Enums\TherapySessionStatus;
use App\Enums\TherapySessionType;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\SessionParticipant;
use App\Models\TherapySession;
use App\Models\User;
use App\Notifications\SessionObserverInviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SessionVideoObserverTest extends TestCase
{
    use RefreshDatabase;

    private function onlineSession(User $professional, Patient $patient, SessionMode $mode = SessionMode::Individual): TherapySession
    {
        return TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'type' => TherapySessionType::Online,
            'session_mode' => $mode,
            'status' => TherapySessionStatus::Scheduled,
        ]);
    }

    public function test_store_with_observer_creates_participant_and_sends_invite(): void
    {
        Notification::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $response = $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'patient_id' => $patient->id,
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '10:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::WithObserver->value,
            'session_observers' => [
                [
                    'source' => 'external',
                    'name' => 'Dr. Supervisor',
                    'email' => 'supervisor@example.com',
                ],
            ],
        ]);

        $response->assertRedirect();

        $session = TherapySession::query()->latest('id')->first();
        $this->assertSame(SessionMode::WithObserver, $session->session_mode);

        $this->assertDatabaseHas('session_participants', [
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Observer->value,
            'email' => 'supervisor@example.com',
            'display_name' => 'Dr. Supervisor',
        ]);

        Notification::assertSentOnDemand(
            SessionObserverInviteNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'supervisor@example.com',
        );
    }

    public function test_observer_can_consent_and_join_room(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $session = $this->onlineSession($professional, $patient, SessionMode::WithObserver);

        $observer = SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Observer,
            'display_name' => 'Supervisor',
            'email' => 'supervisor@example.com',
            'guest_token' => 'observer-token-123',
        ]);

        $this->get(route('session-video.guest', ['token' => $observer->guest_token]))
            ->assertOk()
            ->assertSee(__('Antes de entrar na sala'), false)
            ->assertSee(__('Como observador'), false);

        $this->post(route('session-video.consent', ['token' => $observer->guest_token]), [
            'join_consent' => '1',
            'recording_consent' => '1',
        ])->assertRedirect(route('session-video.guest', ['token' => $observer->guest_token]));

        $observer->refresh();
        $this->assertNotNull($observer->join_consent_at);
        $this->assertNotNull($observer->recording_consent_at);

        $this->get(route('session-video.guest', ['token' => $observer->guest_token]))
            ->assertOk()
            ->assertSee(__('Escuta / supervisão'), false)
            ->assertSee(__('Modo observador — áudio e vídeo desligados.'), false);
    }

    public function test_opening_video_room_syncs_participants(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $session = $this->onlineSession($professional, $patient);

        $this->actingAs($professional)->get(route('therapy-sessions.video.room', $session));

        $this->assertDatabaseHas('session_participants', [
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Host->value,
            'user_id' => $professional->id,
        ]);

        $this->assertDatabaseHas('session_participants', [
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Patient->value,
            'patient_id' => $patient->id,
        ]);
    }

    public function test_video_room_shows_observer_link_when_configured(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $session = $this->onlineSession($professional, $patient, SessionMode::WithObserver);

        SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Observer,
            'display_name' => 'Supervisor',
            'email' => 'supervisor@example.com',
            'guest_token' => 'obs-link-token',
        ]);

        $response = $this->actingAs($professional)->get(route('therapy-sessions.video.room', $session));

        $response->assertOk();
        $response->assertSee(__('Links dos observadores'), false);
        $response->assertSee(__('Participantes'), false);
    }

    public function test_store_with_professional_observer_from_clinic_team(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => UserRole::Professional]);
        $colleague = User::factory()->create([
            'role' => UserRole::Professional,
            'clinic_owner_id' => $owner->id,
            'email' => 'colega@example.com',
        ]);
        $patient = Patient::factory()->create(['professional_id' => $owner->id]);

        $response = $this->actingAs($owner)->post(route('therapy-sessions.store'), [
            'patient_id' => $patient->id,
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '11:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::WithObserver->value,
            'session_observers' => [
                [
                    'source' => 'professional',
                    'professional_id' => $colleague->id,
                ],
            ],
        ]);

        $response->assertRedirect();

        $session = TherapySession::query()->latest('id')->first();

        $this->assertDatabaseHas('session_participants', [
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Observer->value,
            'user_id' => $colleague->id,
        ]);

        Notification::assertSentOnDemand(SessionObserverInviteNotification::class);
    }

    public function test_store_with_multiple_observers_creates_all_participants(): void
    {
        Notification::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $response = $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'patient_id' => $patient->id,
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '14:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::WithObserver->value,
            'session_observers' => [
                [
                    'source' => 'external',
                    'name' => 'Supervisor A',
                    'email' => 'supervisor-a@example.com',
                ],
                [
                    'source' => 'external',
                    'name' => 'Supervisor B',
                    'email' => 'supervisor-b@example.com',
                ],
            ],
        ]);

        $response->assertRedirect();

        $session = TherapySession::query()->latest('id')->first();

        $this->assertSame(2, SessionParticipant::query()
            ->where('therapy_session_id', $session->id)
            ->where('role', SessionParticipantRole::Observer->value)
            ->count());

        Notification::assertSentOnDemandTimes(SessionObserverInviteNotification::class, 2);
    }

    public function test_store_with_observer_without_patient_succeeds(): void
    {
        Notification::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        Patient::factory()->create(['professional_id' => $professional->id]);

        $response = $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '16:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::WithObserver->value,
            'session_observers' => [
                [
                    'source' => 'external',
                    'name' => 'Supervisor Escuta',
                    'email' => 'escuta@example.com',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $session = TherapySession::query()->latest('id')->first();
        $this->assertNull($session->patient_id);
        $this->assertSame(SessionMode::WithObserver, $session->session_mode);

        $this->assertDatabaseHas('session_participants', [
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Observer->value,
            'email' => 'escuta@example.com',
        ]);
    }

    public function test_store_individual_requires_patient(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $response = $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '09:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::Individual->value,
        ]);

        $response->assertSessionHasErrors('patient_id');
    }

    public function test_video_room_renders_for_observer_session_without_patient(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $session = TherapySession::factory()->create([
            'patient_id' => null,
            'professional_id' => $professional->id,
            'type' => TherapySessionType::Online,
            'session_mode' => SessionMode::WithObserver,
            'status' => TherapySessionStatus::Scheduled,
        ]);

        SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Observer,
            'display_name' => 'Supervisor',
            'email' => 'supervisor@example.com',
            'guest_token' => 'obs-room-token',
        ]);

        $this->actingAs($professional)
            ->get(route('therapy-sessions.video.room', $session))
            ->assertOk()
            ->assertSee(__('Escuta / supervisão'), false)
            ->assertSee(__('Links dos observadores'), false);
    }

    public function test_show_observer_session_without_patient_renders(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $session = TherapySession::factory()->create([
            'patient_id' => null,
            'professional_id' => $professional->id,
            'type' => TherapySessionType::Online,
            'session_mode' => SessionMode::WithObserver,
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $this->actingAs($professional)
            ->get(route('therapy-sessions.show', $session))
            ->assertOk()
            ->assertSee(__('Escuta / supervisão'), false);
    }
}
