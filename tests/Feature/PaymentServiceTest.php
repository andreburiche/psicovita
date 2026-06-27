<?php

namespace Tests\Feature;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TherapySession;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_sets_split_and_paid_at_when_paid(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $service = app(PaymentService::class);

        $payment = $service->create([
            'patient_id' => $patient->id,
            'amount' => 150,
            'status' => PaymentStatus::Paid->value,
        ], $user);

        $this->assertSame('150.00', $payment->amount);
        $this->assertSame('15.00', $payment->platform_fee);
        $this->assertSame('135.00', $payment->professional_amount);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame(PaymentGateway::Manual, $payment->gateway);
    }

    public function test_create_from_session_links_patient_and_session(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $session = TherapySession::withoutEvents(function () use ($user, $patient) {
            return TherapySession::factory()->create([
                'professional_id' => $user->id,
                'patient_id' => $patient->id,
            ]);
        });

        $payment = app(PaymentService::class)->createFromSession($session, 200);

        $this->assertSame($patient->id, $payment->patient_id);
        $this->assertSame($session->id, $payment->therapy_session_id);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame('20.00', $payment->platform_fee);
        $this->assertSame('180.00', $payment->professional_amount);
    }

    public function test_mark_as_paid_updates_status_and_timestamp(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
        ]);

        $updated = app(PaymentService::class)->markAsPaid($payment, [
            'gateway' => PaymentGateway::Asaas->value,
            'external_id' => 'asaas_chg_test123',
        ]);

        $this->assertSame(PaymentStatus::Paid, $updated->status);
        $this->assertNotNull($updated->paid_at);
        $this->assertSame('asaas_chg_test123', $updated->external_id);
    }

    public function test_webhook_confirms_payment_when_external_id_matches(): void
    {
        config(['asaas.webhook_token' => 'secret-token']);

        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'external_id' => 'asaas_chg_webhook1',
            'gateway' => PaymentGateway::Asaas,
        ]);

        $response = $this->postJson(route('webhooks.asaas'), [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'asaas_chg_webhook1',
                'status' => 'RECEIVED',
            ],
        ], [
            'asaas-access-token' => 'secret-token',
        ]);

        $response->assertOk()->assertJsonPath('payment_id', $payment->id);

        $payment->refresh();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertDatabaseHas('payment_gateway_transactions', [
            'payment_id' => $payment->id,
            'external_id' => 'asaas_chg_webhook1',
        ]);
    }

    public function test_webhook_marks_payment_as_overdue(): void
    {
        config(['asaas.webhook_token' => 'secret-token']);

        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'external_id' => 'asaas_chg_overdue1',
            'gateway' => PaymentGateway::Asaas,
        ]);

        $response = $this->postJson(route('webhooks.asaas'), [
            'event' => 'PAYMENT_OVERDUE',
            'payment' => [
                'id' => 'asaas_chg_overdue1',
                'status' => 'OVERDUE',
            ],
        ], [
            'asaas-access-token' => 'secret-token',
        ]);

        $response->assertOk()->assertJsonPath('payment_id', $payment->id);

        $payment->refresh();
        $this->assertSame(PaymentStatus::Overdue, $payment->status);
        $this->assertNull($payment->paid_at);
    }

    public function test_payment_store_via_controller_uses_service(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $this->actingAs($user);

        $this->post(route('payments.store'), [
            'patient_id' => $patient->id,
            'amount' => 100,
            'status' => PaymentStatus::Paid->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'patient_id' => $patient->id,
            'platform_fee' => 10,
            'professional_amount' => 90,
        ]);
    }

    public function test_quick_update_changes_status_from_show_actions(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($user)
            ->patch(route('payments.quick-update', $payment), [
                'status' => PaymentStatus::Paid->value,
            ])
            ->assertRedirect(route('payments.show', $payment));

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
    }

    public function test_quick_update_changes_payment_method(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'payment_method' => null,
        ]);

        $this->actingAs($user)
            ->patch(route('payments.quick-update', $payment), [
                'payment_method' => \App\Enums\PaymentMethod::Pix->value,
            ])
            ->assertRedirect(route('payments.show', $payment));

        $this->assertSame(\App\Enums\PaymentMethod::Pix, $payment->fresh()->payment_method);
    }
}
