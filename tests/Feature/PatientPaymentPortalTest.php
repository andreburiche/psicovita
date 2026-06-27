<?php

namespace Tests\Feature;

use App\Enums\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TherapySessionStatus;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TherapySession;
use App\Models\User;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPaymentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_creation_auto_generates_pending_payment(): void
    {
        $professional = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $session = TherapySession::factory()->create([
            'professional_id' => $professional->id,
            'patient_id' => $patient->id,
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $this->assertDatabaseHas('payments', [
            'patient_id' => $patient->id,
            'therapy_session_id' => $session->id,
            'status' => PaymentStatus::Pending->value,
        ]);
    }

    public function test_patient_can_view_and_pay_own_payment(): void
    {
        $professional = User::factory()->create();
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'email' => 'paciente.portal@example.com',
        ]);
        $patient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'paciente.portal@example.com',
            'email_hash' => ContactHasher::emailHash('paciente.portal@example.com'),
        ]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'amount' => 150,
            'payment_method' => PaymentMethod::Pix,
        ]);

        $this->actingAs($patientUser);

        $this->get(route('patient.payments.index'))
            ->assertOk()
            ->assertSee('150,00', false);

        $this->post(route('patient.payments.pay', $payment))
            ->assertRedirect(route('patient.payments.show', $payment));

        $payment->refresh();
        $this->assertSame(PaymentGateway::Asaas, $payment->gateway);
        $this->assertNotNull($payment->external_id);
        $this->assertNotNull($payment->gateway_meta['pix']['payload'] ?? null);
    }

    public function test_patient_must_choose_payment_method_when_not_set(): void
    {
        $professional = User::factory()->create();
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'email' => 'escolha@example.com',
        ]);
        $patient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'escolha@example.com',
            'email_hash' => ContactHasher::emailHash('escolha@example.com'),
        ]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'amount' => 120,
            'payment_method' => null,
        ]);

        $this->actingAs($patientUser);

        $this->post(route('patient.payments.pay', $payment))
            ->assertSessionHasErrors('payment_method');

        $this->post(route('patient.payments.pay', $payment), [
            'payment_method' => PaymentMethod::Pix->value,
        ])->assertRedirect(route('patient.payments.show', $payment));

        $payment->refresh();
        $this->assertSame(PaymentMethod::Pix, $payment->payment_method);
        $this->assertNotNull($payment->gateway_meta['pix']['payload'] ?? null);
    }

    public function test_card_payment_generates_invoice_not_pix(): void
    {
        $professional = User::factory()->create();
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'email' => 'cartao.portal@example.com',
        ]);
        $patient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'cartao.portal@example.com',
            'email_hash' => ContactHasher::emailHash('cartao.portal@example.com'),
        ]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'amount' => 200,
            'payment_method' => PaymentMethod::Card,
        ]);

        $this->actingAs($patientUser);

        $this->post(route('patient.payments.pay', $payment))
            ->assertRedirect(route('patient.payments.show', $payment))
            ->assertSessionHas('status', __('Link de pagamento gerado. Conclua o pagamento com cartão no ambiente seguro.'));

        $payment->refresh();
        $this->assertSame(PaymentMethod::Card, $payment->payment_method);
        $this->assertSame('CREDIT_CARD', $payment->gateway_meta['billing_type'] ?? null);
        $this->assertNotNull($payment->gateway_meta['invoice_url'] ?? null);
        $this->assertNull($payment->gateway_meta['pix'] ?? null);

        $this->get(route('patient.payments.show', $payment))
            ->assertOk()
            ->assertSee(__('Pagar com cartão'), false)
            ->assertDontSee(__('Copiar código PIX'), false);
    }

    public function test_patient_cannot_access_other_patients_payment(): void
    {
        $professional = User::factory()->create();
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'email' => 'meu@example.com',
        ]);
        Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'meu@example.com',
            'email_hash' => ContactHasher::emailHash('meu@example.com'),
        ]);

        $otherPatient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'outro@example.com',
            'email_hash' => ContactHasher::emailHash('outro@example.com'),
        ]);
        $otherPayment = Payment::factory()->create([
            'patient_id' => $otherPatient->id,
        ]);

        $this->actingAs($patientUser);

        $this->get(route('patient.payments.show', $otherPayment))->assertNotFound();
    }

    public function test_patient_home_shows_pending_payments_summary(): void
    {
        $professional = User::factory()->create();
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'email' => 'resumo@example.com',
        ]);
        $patient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'resumo@example.com',
            'email_hash' => ContactHasher::emailHash('resumo@example.com'),
        ]);
        Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'amount' => 80,
        ]);
        Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Overdue,
            'amount' => 45.50,
        ]);
        Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Paid,
            'amount' => 200,
        ]);

        $this->actingAs($patientUser);

        $this->get(route('patient.home'))
            ->assertOk()
            ->assertSee('2 cobranças pendentes', false)
            ->assertSee('125,50', false)
            ->assertSee(route('patient.payments.index'), false);
    }

    public function test_webhook_marks_portal_payment_as_overdue(): void
    {
        config(['asaas.webhook_token' => 'portal-token']);

        $professional = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'gateway' => PaymentGateway::Asaas,
            'external_id' => 'asaas_chg_portal_overdue',
        ]);

        $this->postJson(route('webhooks.asaas'), [
            'event' => 'PAYMENT_OVERDUE',
            'payment' => [
                'id' => 'asaas_chg_portal_overdue',
                'status' => 'OVERDUE',
            ],
        ], ['asaas-access-token' => 'portal-token'])->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Overdue, $payment->status);
    }

    public function test_webhook_marks_portal_payment_as_paid(): void
    {
        config(['asaas.webhook_token' => 'portal-token']);

        $professional = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'gateway' => PaymentGateway::Asaas,
            'external_id' => 'asaas_chg_portal99',
        ]);

        $this->postJson(route('webhooks.asaas'), [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'asaas_chg_portal99',
                'status' => 'RECEIVED',
            ],
        ], ['asaas-access-token' => 'portal-token'])->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertNotNull($payment->paid_at);
    }
}
