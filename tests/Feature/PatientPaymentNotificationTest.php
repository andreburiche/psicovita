<?php

namespace Tests\Feature;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\TherapySessionStatus;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TherapySession;
use App\Models\User;
use App\Notifications\PatientPaymentDueNotification;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PatientPaymentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_payment_notifies_patient_with_portal_account(): void
    {
        Notification::fake();

        $professional = User::factory()->create();
        $email = 'notify@example.com';
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'email' => $email,
        ]);
        $patient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => $email,
            'email_hash' => ContactHasher::emailHash($email),
        ]);

        TherapySession::factory()->create([
            'professional_id' => $professional->id,
            'patient_id' => $patient->id,
            'status' => TherapySessionStatus::Scheduled,
        ]);

        Notification::assertSentTo($patientUser, PatientPaymentDueNotification::class, function ($notification) {
            return $notification->context === 'created';
        });
    }

    public function test_payment_reminders_command_notifies_old_pending_payments(): void
    {
        Notification::fake();

        $professional = User::factory()->create();
        $email = 'reminder@example.com';
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'email' => $email,
        ]);
        $patient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => $email,
            'email_hash' => ContactHasher::emailHash($email),
        ]);
        Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'created_at' => now()->subDays(5),
        ]);

        $this->artisan('psiconecta:payment-reminders')->assertSuccessful();

        Notification::assertSentTo($patientUser, PatientPaymentDueNotification::class, function ($notification) {
            return $notification->context === 'reminder';
        });
    }

    public function test_webhook_overdue_notifies_patient(): void
    {
        Notification::fake();
        config(['asaas.webhook_token' => 'notify-token']);

        $professional = User::factory()->create();
        $email = 'overdue@example.com';
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'email' => $email,
        ]);
        $patient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => $email,
            'email_hash' => ContactHasher::emailHash($email),
        ]);
        Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'gateway' => PaymentGateway::Asaas,
            'external_id' => 'asaas_chg_notify_overdue',
        ]);

        $this->postJson(route('webhooks.asaas'), [
            'event' => 'PAYMENT_OVERDUE',
            'payment' => [
                'id' => 'asaas_chg_notify_overdue',
                'status' => 'OVERDUE',
            ],
        ], ['asaas-access-token' => 'notify-token'])->assertOk();

        Notification::assertSentTo($patientUser, PatientPaymentDueNotification::class, function ($notification) {
            return $notification->context === 'overdue';
        });
    }

    public function test_opening_payment_notification_redirects_to_portal(): void
    {
        $professional = User::factory()->create();
        $email = 'open@example.com';
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'email' => $email,
        ]);
        $patient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => $email,
            'email_hash' => ContactHasher::emailHash($email),
        ]);
        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
        ]);

        $patientUser->notify(new PatientPaymentDueNotification($payment, 'created'));
        $notification = $patientUser->notifications()->first();

        $this->actingAs($patientUser);

        $this->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('patient.payments.show', $payment));
    }
}
