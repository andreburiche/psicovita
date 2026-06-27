<?php

namespace Tests\Feature;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ProfessionalClinicalPaymentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfessionalPaymentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_paid_notifies_professional(): void
    {
        Notification::fake();
        config(['asaas.webhook_token' => 'pro-token']);

        $professional = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'amount' => 180,
            'gateway' => PaymentGateway::Asaas,
            'external_id' => 'asaas_chg_pro_notify',
        ]);

        $this->postJson(route('webhooks.asaas'), [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'asaas_chg_pro_notify',
                'status' => 'RECEIVED',
            ],
        ], ['asaas-access-token' => 'pro-token'])->assertOk();

        Notification::assertSentTo($professional, ProfessionalClinicalPaymentNotification::class, function ($notification) {
            return $notification->context === 'received';
        });
    }

    public function test_webhook_overdue_notifies_professional(): void
    {
        Notification::fake();
        config(['asaas.webhook_token' => 'pro-token']);

        $professional = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'gateway' => PaymentGateway::Asaas,
            'external_id' => 'asaas_chg_pro_overdue',
        ]);

        $this->postJson(route('webhooks.asaas'), [
            'event' => 'PAYMENT_OVERDUE',
            'payment' => [
                'id' => 'asaas_chg_pro_overdue',
                'status' => 'OVERDUE',
            ],
        ], ['asaas-access-token' => 'pro-token'])->assertOk();

        Notification::assertSentTo($professional, ProfessionalClinicalPaymentNotification::class, function ($notification) {
            return $notification->context === 'overdue';
        });
    }

    public function test_opening_payment_notification_redirects_to_financial_show(): void
    {
        $professional = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Paid,
            'amount' => 120,
        ]);

        $professional->notify(new ProfessionalClinicalPaymentNotification($payment->load('patient'), 'received'));
        $notification = $professional->notifications()->first();

        $this->actingAs($professional);

        $this->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('payments.show', $payment));
    }
}
