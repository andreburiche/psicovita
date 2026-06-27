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
use App\Notifications\SessionFamilyGuestInviteNotification;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SessionVideoFamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_family_session_creates_guests_and_sends_invites(): void
    {
        Notification::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $response = $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'patient_id' => $patient->id,
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '11:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::Family->value,
            'family_guest_name' => ['Maria Convidada'],
            'family_guest_email' => ['maria.family@example.com'],
        ]);

        $response->assertRedirect();

        $session = TherapySession::query()->latest('id')->first();
        $this->assertSame(SessionMode::Family, $session->session_mode);

        $this->assertDatabaseHas('session_participants', [
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Guest->value,
            'email' => 'maria.family@example.com',
            'display_name' => 'Maria Convidada',
        ]);

        Notification::assertSentOnDemand(
            SessionFamilyGuestInviteNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'maria.family@example.com',
        );
    }

    public function test_family_guest_links_to_existing_patient_by_email(): void
    {
        Notification::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $primary = Patient::factory()->create(['professional_id' => $professional->id]);
        $partner = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'parceiro@example.com',
            'email_hash' => ContactHasher::emailHash('parceiro@example.com'),
            'name' => 'Parceiro Silva',
        ]);

        $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'patient_id' => $primary->id,
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '15:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::Family->value,
            'family_guest_name' => ['Parceiro Silva'],
            'family_guest_email' => ['parceiro@example.com'],
        ]);

        $session = TherapySession::query()->latest('id')->first();

        $guest = SessionParticipant::query()
            ->where('therapy_session_id', $session->id)
            ->where('role', SessionParticipantRole::Guest)
            ->first();

        $this->assertNotNull($guest);
        $this->assertSame($partner->id, $guest->patient_id);
    }

    public function test_family_guest_can_consent_and_join(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $session = TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'type' => TherapySessionType::Online,
            'session_mode' => SessionMode::Family,
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $guest = SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Guest,
            'display_name' => 'Convidado',
            'email' => 'convidado@example.com',
            'guest_token' => 'family-guest-token',
        ]);

        $this->get(route('session-video.guest', ['token' => $guest->guest_token]))
            ->assertOk()
            ->assertSee(__('Antes de entrar na sala'), false);

        $this->post(route('session-video.consent', ['token' => $guest->guest_token]), [
            'join_consent' => '1',
            'recording_consent' => '1',
        ])->assertRedirect();

        $this->get(route('session-video.guest', ['token' => $guest->guest_token]))
            ->assertOk()
            ->assertSee(__('Sessão de casal/família'), false);
    }

    public function test_family_session_accepts_patient_from_system(): void
    {
        Notification::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $primary = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'primary@example.com',
        ]);
        $partner = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'parceiro@example.com',
        ]);

        $response = $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'patient_id' => $primary->id,
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '16:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::Family->value,
            'family_patient_ids' => [$partner->id],
        ]);

        $response->assertRedirect();

        $session = TherapySession::query()->latest('id')->first();

        $this->assertDatabaseHas('session_participants', [
            'therapy_session_id' => $session->id,
            'patient_id' => $partner->id,
            'role' => SessionParticipantRole::Guest->value,
        ]);
    }

    public function test_family_session_requires_at_least_one_guest(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $response = $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'patient_id' => $patient->id,
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '12:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::Family->value,
        ]);

        $response->assertSessionHasErrors('family_patient_ids');
    }
}
