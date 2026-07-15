<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class PaymentSplitUsesPracticeOwnerWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_split_uses_clinic_owner_wallet_not_team_member(): void
    {
        config(['asaas.split_enabled' => true]);

        $owner = User::factory()->create([
            'role' => UserRole::Professional,
            'asaas_wallet_id' => 'wal_owner_wallet_12345',
        ]);

        $member = User::factory()->create([
            'role' => UserRole::Professional,
            'clinic_owner_id' => $owner->id,
            'asaas_wallet_id' => 'wal_member_wallet_99999',
        ]);

        $patient = Patient::factory()->create([
            'professional_id' => $owner->id,
        ]);

        // Simula paciente atendido no contexto da prática (professional_id = dono).
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'amount' => 200,
            'status' => PaymentStatus::Pending,
            'payment_method' => PaymentMethod::Pix,
            'professional_amount' => 180,
            'platform_fee' => 20,
        ]);

        $service = app(PaymentService::class);
        $method = new ReflectionMethod(PaymentService::class, 'buildChargeSplit');
        $method->setAccessible(true);
        $split = $method->invoke($service, $payment);

        $this->assertIsArray($split);
        $this->assertSame('wal_owner_wallet_12345', $split['wallet_id']);
        $this->assertEquals(180.0, $split['fixed_value']);
        $this->assertNotSame('wal_member_wallet_99999', $split['wallet_id']);

        // Quando o professional do paciente aponta ao dono, o member wallet nunca entra.
        $this->assertTrue($member->isClinicTeamMember());
    }
}
