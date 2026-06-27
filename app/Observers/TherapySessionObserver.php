<?php

namespace App\Observers;

use App\Enums\TherapySessionStatus;
use App\Models\TherapySession;
use App\Services\PaymentService;

class TherapySessionObserver
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

    public function created(TherapySession $session): void
    {
        if ($session->skipAutoPayment) {
            return;
        }

        if (! config('payment.auto_charge_on_session_created', true) && ! $session->forceAutoPayment) {
            return;
        }

        if ($session->status === TherapySessionStatus::Cancelled) {
            return;
        }

        if ($session->patient_id && $session->payments()->where('patient_id', $session->patient_id)->exists()) {
            return;
        }

        if (! $session->patient_id) {
            return;
        }

        $this->payments->createFromSession($session, $session->paymentAmountOverride);
    }
}
