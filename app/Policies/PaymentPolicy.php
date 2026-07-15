<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Auth\Access\Response;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProfessional() || $user->usesPatientPortalExperience();
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->isProfessional() && $payment->patient->professional_id === $user->clinicalPracticeId()) {
            return true;
        }

        return app(PaymentService::class)->patientOwnsPayment($user, $payment);
    }

    public function create(User $user): bool
    {
        return $user->isProfessional();
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->isProfessional() && $payment->patient->professional_id === $user->clinicalPracticeId();
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->isProfessional() && $payment->patient->professional_id === $user->clinicalPracticeId();
    }

    public function pay(User $user, Payment $payment): Response|bool
    {
        if (! app(PaymentService::class)->patientOwnsPayment($user, $payment)) {
            return false;
        }

        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Overdue], true)) {
            return Response::deny(__('Este pagamento já foi liquidado ou cancelado.'));
        }

        return true;
    }

    public function reportPaid(User $user, Payment $payment): Response|bool
    {
        if (! app(PaymentService::class)->patientOwnsPayment($user, $payment)) {
            return false;
        }

        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Overdue], true)) {
            return Response::deny(__('Este pagamento já foi liquidado ou cancelado.'));
        }

        $mode = $payment->gateway_meta['checkout_mode'] ?? null;

        if ($mode !== 'manual') {
            return Response::deny(__('Só é possível indicar «Já paguei» em cobranças PIX manuais.'));
        }

        return true;
    }

    public function confirmManual(User $user, Payment $payment): bool
    {
        if (! $user->isProfessional() || $user->isClinicTeamMember()) {
            return false;
        }

        if ($payment->status !== PaymentStatus::PendingConfirmation) {
            return false;
        }

        return (int) $payment->patient?->professional_id === (int) $user->clinicalPracticeId();
    }

    public function restore(User $user, Payment $payment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return false;
    }
}
