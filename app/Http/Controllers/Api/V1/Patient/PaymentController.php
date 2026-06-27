<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\PatientApi\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $payments = $this->payments->paginatePortalPayments(
            $request->user(),
            (int) $request->query('per_page', 15)
        );

        return PaymentResource::collection($payments)->response();
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);
        $payment->load(['therapySession']);
        $payment = $this->payments->syncPixCheckoutForDisplay($payment);

        return response()->json([
            'data' => PaymentResource::make($payment),
        ]);
    }

    public function pay(Request $request, Payment $payment): JsonResponse
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
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $payment = $this->payments->syncPixCheckoutForDisplay($payment->load(['therapySession']));

        return response()->json([
            'message' => $this->payments->portalPaymentSuccessMessage($payment),
            'data' => PaymentResource::make($payment),
        ]);
    }
}
