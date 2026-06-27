<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\SessionMode;
use App\Enums\SessionParticipantRole;
use App\Enums\TherapySessionType;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\SessionParticipant;
use App\Models\TherapySession;
use App\Models\User;
use App\Services\SessionBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionBillingOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_shows_partial_when_only_one_observer_paid(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $session = TherapySession::factory()->create([
            'professional_id' => $professional->id,
            'patient_id' => null,
            'session_mode' => SessionMode::WithObserver,
            'type' => TherapySessionType::Online,
        ]);

        $livia = SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Observer,
            'display_name' => 'Livia Estagne',
            'email' => 'livia@example.com',
            'guest_token' => Str::random(48),
        ]);

        SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Observer,
            'display_name' => 'Andre',
            'email' => 'andre@example.com',
            'guest_token' => Str::random(48),
        ]);

        $patient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'name' => 'Livia Estagne',
            'email' => 'livia@example.com',
        ]);

        Payment::factory()->create([
            'patient_id' => $patient->id,
            'therapy_session_id' => $session->id,
            'amount' => 150,
            'status' => PaymentStatus::Paid,
        ]);

        $overview = app(SessionBillingService::class)->overview($session->fresh(['payments.patient', 'participants']));

        $this->assertTrue($overview['is_multi_participant']);
        $this->assertFalse($overview['all_paid']);
        $this->assertTrue($overview['has_partial']);
        $this->assertSame(1, $overview['paid_count']);
        $this->assertSame(2, $overview['total_participants']);
        $this->assertSame(1, $overview['missing_count']);
        $this->assertStringContainsString('Parcial', $overview['aggregate_label']);

        $liviaLine = collect($overview['lines'])->first(fn (array $line) => $line['email'] === 'livia@example.com');
        $andreLine = collect($overview['lines'])->first(fn (array $line) => $line['email'] === 'andre@example.com');

        $this->assertSame(PaymentStatus::Paid, $liviaLine['payment']?->status);
        $this->assertNull($andreLine['payment']);
    }
}
