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
use App\Notifications\SessionGroupMemberInviteNotification;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SessionVideoGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_group_session_creates_members_and_sends_invites(): void
    {
        Notification::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patientA = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'grupo.a@example.com',
            'email_hash' => ContactHasher::emailHash('grupo.a@example.com'),
        ]);
        $patientB = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'grupo.b@example.com',
            'email_hash' => ContactHasher::emailHash('grupo.b@example.com'),
        ]);

        $response = $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '10:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::Group->value,
            'group_patient_ids' => [$patientA->id, $patientB->id],
        ]);

        $response->assertRedirect();

        $session = TherapySession::query()->latest('id')->first();
        $this->assertSame(SessionMode::Group, $session->session_mode);
        $this->assertSame($patientA->id, $session->patient_id);

        $this->assertDatabaseHas('session_participants', [
            'therapy_session_id' => $session->id,
            'patient_id' => $patientA->id,
            'role' => SessionParticipantRole::Patient->value,
        ]);
        $this->assertDatabaseHas('session_participants', [
            'therapy_session_id' => $session->id,
            'patient_id' => $patientB->id,
            'role' => SessionParticipantRole::Patient->value,
        ]);

        Notification::assertSentOnDemand(SessionGroupMemberInviteNotification::class, 2);
    }

    public function test_group_session_requires_at_least_two_members(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $response = $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '10:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::Group->value,
            'group_patient_ids' => [$patient->id],
        ]);

        $response->assertSessionHasErrors('group_patient_ids');
    }

    public function test_group_members_get_individual_tokens_on_video_room(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patientA = Patient::factory()->create(['professional_id' => $professional->id]);
        $patientB = Patient::factory()->create(['professional_id' => $professional->id]);
        $session = TherapySession::factory()->create([
            'patient_id' => $patientA->id,
            'professional_id' => $professional->id,
            'type' => TherapySessionType::Online,
            'session_mode' => SessionMode::Group,
            'status' => TherapySessionStatus::Scheduled,
        ]);

        SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Patient,
            'patient_id' => $patientA->id,
            'display_name' => $patientA->name,
        ]);
        SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Patient,
            'patient_id' => $patientB->id,
            'display_name' => $patientB->name,
            'guest_token' => 'member-b-token',
        ]);

        $response = $this->actingAs($professional)->get(route('therapy-sessions.video.room', $session));

        $response->assertOk();
        $response->assertSee(__('Links dos membros do grupo'), false);
        $response->assertSee($patientB->name, false);

        $memberA = $session->fresh()->participants()->where('patient_id', $patientA->id)->first();
        $memberB = $session->fresh()->participants()->where('patient_id', $patientB->id)->first();

        $this->assertNotNull($memberA->guest_token);
        $this->assertNotNull($memberB->guest_token);
        $this->assertNotSame($memberA->guest_token, $memberB->guest_token);
    }

    public function test_group_member_can_join_after_consent(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $session = TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'type' => TherapySessionType::Online,
            'session_mode' => SessionMode::Group,
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $member = SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Patient,
            'patient_id' => $patient->id,
            'display_name' => $patient->name,
            'guest_token' => 'group-member-token',
        ]);

        $this->post(route('session-video.consent', ['token' => $member->guest_token]), [
            'join_consent' => '1',
            'recording_consent' => '1',
        ])->assertRedirect();

        $member->refresh();
        $this->assertNotNull($member->joined_at);

        $this->get(route('session-video.guest', ['token' => $member->guest_token]))
            ->assertOk()
            ->assertSee(__('Grupo terapêutico'), false);
    }
}
