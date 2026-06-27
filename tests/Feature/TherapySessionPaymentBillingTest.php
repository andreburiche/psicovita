<?php

namespace Tests\Feature;

use App\Enums\SessionMode;
use App\Enums\SessionParticipantRole;
use App\Enums\PaymentStatus;
use App\Enums\TherapySessionStatus;
use App\Enums\TherapySessionType;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\SessionParticipant;
use App\Models\TherapySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TherapySessionPaymentBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_session_creates_payment_with_custom_amount(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '10:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::Individual->value,
            'patient_id' => $patient->id,
            'generate_payment' => '1',
            'payment_amount' => '220.50',
        ])->assertRedirect();

        $session = TherapySession::query()->latest('id')->first();

        $this->assertDatabaseHas('payments', [
            'therapy_session_id' => $session->id,
            'patient_id' => $patient->id,
            'amount' => '220.50',
        ]);
    }

    public function test_group_session_uses_selected_billing_patient(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patientA = Patient::factory()->create(['professional_id' => $professional->id]);
        $patientB = Patient::factory()->create(['professional_id' => $professional->id]);

        $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '11:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
            'session_mode' => SessionMode::Group->value,
            'group_patient_ids' => [$patientA->id, $patientB->id],
            'billing_patient_id' => $patientB->id,
            'generate_payment' => '1',
            'payment_amount' => '300',
        ])->assertRedirect();

        $session = TherapySession::query()->latest('id')->first();
        $this->assertSame($patientB->id, $session->patient_id);

        $payment = Payment::query()->where('therapy_session_id', $session->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame($patientB->id, $payment->patient_id);
        $this->assertSame('300.00', $payment->amount);
    }

    public function test_session_can_be_created_without_payment_when_checkbox_disabled(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $this->actingAs($professional)->post(route('therapy-sessions.store'), [
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '12:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::InPerson->value,
            'patient_id' => $patient->id,
            'generate_payment' => '0',
        ])->assertRedirect();

        $session = TherapySession::query()->latest('id')->first();
        $this->assertNull(Payment::query()->where('therapy_session_id', $session->id)->first());
    }

    public function test_payment_create_prefills_session_from_query_string(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $session = TherapySession::factory()->create([
            'professional_id' => $professional->id,
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($professional)->get(route('payments.create', [
            'therapy_session_id' => $session->id,
            'patient_id' => $patient->id,
        ]));

        $response->assertOk();
        $response->assertSee('value="'.$session->id.'"', false);
        $response->assertSee($patient->name);
    }

    public function test_payment_create_shows_event_participants_for_escuta_session(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $session = TherapySession::factory()->create([
            'professional_id' => $professional->id,
            'patient_id' => null,
            'session_mode' => SessionMode::WithObserver,
            'type' => TherapySessionType::Online,
        ]);

        SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Observer,
            'display_name' => 'Andre',
            'email' => 'andre@example.com',
            'guest_token' => Str::random(48),
        ]);

        $response = $this->actingAs($professional)->get(route('payments.create', [
            'therapy_session_id' => $session->id,
        ]));

        $response->assertOk();
        $response->assertSee(__('Participante do evento'), false);
        $response->assertSee('Andre', false);
        $response->assertSee('andre@example.com', false);
    }

    public function test_payment_store_from_external_observer_participant_creates_billing_contact(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $session = TherapySession::factory()->create([
            'professional_id' => $professional->id,
            'patient_id' => null,
            'session_mode' => SessionMode::WithObserver,
            'type' => TherapySessionType::Online,
        ]);

        $participant = SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Observer,
            'display_name' => 'Livia',
            'email' => 'livia@example.com',
            'guest_token' => Str::random(48),
        ]);

        $this->actingAs($professional)->post(route('payments.store'), [
            'therapy_session_id' => $session->id,
            'session_participant_id' => $participant->id,
            'amount' => 180,
            'status' => PaymentStatus::Pending->value,
        ])->assertRedirect();

        $patient = Patient::query()->where('professional_id', $professional->id)->first();
        $this->assertNotNull($patient);
        $this->assertSame('Livia', $patient->name);

        $this->assertDatabaseHas('payments', [
            'therapy_session_id' => $session->id,
            'patient_id' => $patient->id,
            'amount' => '180.00',
        ]);
    }
}
