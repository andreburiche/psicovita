<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {
        $this->authorizeResource(Payment::class, 'payment');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Payment::query()
            ->whereHas('patient', fn ($q) => $q->where('professional_id', $request->user()->clinicalPracticeId()))
            ->with(['patient', 'therapySession.patient'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return PaymentResource::collection(
            $query->paginate((int) $request->query('per_page', 20))->withQueryString()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
            'therapy_session_id' => [
                'nullable',
                Rule::exists('therapy_sessions', 'id')->where('professional_id', $request->user()->clinicalPracticeId()),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payment = $this->payments->create($validated, $request->user())->load(['patient', 'therapySession']);

        return PaymentResource::make($payment)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Payment $payment): PaymentResource
    {
        $payment->load(['patient', 'therapySession.patient']);

        return new PaymentResource($payment);
    }

    public function update(Request $request, Payment $payment): PaymentResource
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payment = $this->payments->update($payment, $validated)->load(['patient', 'therapySession']);

        return new PaymentResource($payment);
    }

    public function destroy(Payment $payment): Response
    {
        $payment->delete();

        return response()->noContent();
    }
}
