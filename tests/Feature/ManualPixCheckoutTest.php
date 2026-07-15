<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentMethodPreference;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentAwaitingManualConfirmationNotification;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ManualPixCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_report_manual_pix_paid_and_owner_can_confirm(): void
    {
        Notification::fake();

        $owner = User::factory()->create([
            'role' => UserRole::Professional,
            'asaas_wallet_id' => null,
            'pix_manual_link' => 'email@pix.com',
            'payment_method_preference' => PaymentMethodPreference::Manual,
        ]);

        $email = 'paciente-pix@example.com';
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $owner->id,
            'email' => $email,
        ]);

        $patient = Patient::factory()->create([
            'professional_id' => $owner->id,
            'email' => $email,
            'email_hash' => ContactHasher::emailHash($email),
        ]);

        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'amount' => 150,
            'status' => PaymentStatus::Pending,
            'payment_method' => PaymentMethod::Pix,
            'gateway_meta' => [
                'checkout_mode' => 'manual',
                'pix' => [
                    'payload' => 'email@pix.com',
                    'image_url' => null,
                    'raw' => ['manual' => true],
                ],
            ],
        ]);

        $this->actingAs($patientUser)
            ->post(route('patient.payments.already-paid', $payment))
            ->assertRedirect(route('patient.payments.show', $payment));

        $payment->refresh();
        $this->assertSame(PaymentStatus::PendingConfirmation, $payment->status);

        Notification::assertSentTo($owner, PaymentAwaitingManualConfirmationNotification::class);

        $this->actingAs($owner)
            ->post(route('payments.confirm-manual', $payment))
            ->assertRedirect(route('payments.show', $payment));

        $payment->refresh();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_clinic_owner_can_save_manual_pix_settings(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::Professional,
            'payment_method_preference' => PaymentMethodPreference::Auto,
        ]);

        $this->actingAs($owner)
            ->postJson(route('profile.payment-settings.update'), [
                'payment_method_preference' => 'manual',
                'pix_manual_link' => 'minha-chave-pix',
            ])
            ->assertOk()
            ->assertJsonPath('mode', 'manual')
            ->assertJsonPath('pix_manual_link', 'minha-chave-pix');

        $owner->refresh();
        $this->assertSame(PaymentMethodPreference::Manual, $owner->payment_method_preference);
        $this->assertSame('minha-chave-pix', $owner->pix_manual_link);
    }

    public function test_team_member_cannot_update_payment_settings(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Professional]);
        $member = User::factory()->create([
            'role' => UserRole::Professional,
            'clinic_owner_id' => $owner->id,
        ]);

        $this->actingAs($member)
            ->postJson(route('profile.payment-settings.update'), [
                'payment_method_preference' => 'manual',
                'pix_manual_link' => 'nao-pode',
            ])
            ->assertForbidden();
    }
}
