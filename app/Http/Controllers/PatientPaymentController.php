<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Support\PaymentMethodResolution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $payments = $this->payments->paginatePortalPayments($request->user());
        $pendingTotal = (float) $payments->getCollection()
            ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Overdue])
            ->sum('amount');

        return view('patient.payments.index', [
            'payments' => $payments,
            'pendingTotal' => $pendingTotal,
        ]);
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);
        $payment->load(['therapySession', 'patient.professional']);
        $resolution = $this->payments->resolveCheckoutForPayment($payment);

        if (
            $resolution->isManual()
            && in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Overdue], true)
            && ($payment->gateway_meta['checkout_mode'] ?? null) !== PaymentMethodResolution::MODE_MANUAL
        ) {
            $payment = $this->payments->prepareManualPixCheckout($payment, $resolution);
        } elseif ($resolution->isAsaas()) {
            $payment = $this->payments->syncPixCheckoutForDisplay($payment);
        }

        return view('patient.payments.show', [
            'payment' => $payment,
            'needsMethodChoice' => $this->payments->needsPaymentMethodChoice($payment),
            'checkoutResolution' => $resolution,
        ]);
    }

    public function pay(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('pay', $payment);

        $chosenMethod = null;
        if ($this->payments->needsPaymentMethodChoice($payment)) {
            $validated = $request->validate([
                'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            ]);
            $chosenMethod = PaymentMethod::from($validated['payment_method']);
        }

        try {
            $payment = $this->payments->initiatePortalPayment($payment, $chosenMethod);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()
            ->route('patient.payments.show', $payment)
            ->with('status', $this->payments->portalPaymentSuccessMessage($payment));
    }

    public function alreadyPaid(Payment $payment): RedirectResponse
    {
        $this->authorize('reportPaid', $payment);

        try {
            $this->payments->markAwaitingManualConfirmation($payment);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()
            ->route('patient.payments.show', $payment)
            ->with('status', __('Obrigado! Informámos o profissional. Assim que confirmar o PIX, o pagamento ficará como pago.'));
    }
}
