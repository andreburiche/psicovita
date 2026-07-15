<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SessionMode;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\SessionParticipant;
use App\Models\TherapySession;
use App\Services\PaymentService;
use App\Services\SessionParticipantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly SessionParticipantService $participants,
    ) {
        $this->authorizeResource(Payment::class, 'payment');
    }

    public function index(Request $request): View
    {
        $qRaw = $request->filled('q') ? trim((string) $request->input('q')) : '';
        $filters = [
            'status' => $request->filled('status') ? (string) $request->input('status') : null,
            'patient_id' => $request->filled('patient_id') ? (int) $request->input('patient_id') : null,
            'q' => $qRaw !== '' ? $qRaw : null,
            'from' => $request->filled('from') ? (string) $request->input('from') : null,
            'to' => $request->filled('to') ? (string) $request->input('to') : null,
        ];

        $validated = validator($filters, [
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'patient_id' => ['nullable', 'integer', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
            'q' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ])->validate();

        $query = Payment::query()
            ->whereHas('patient', fn ($q) => $q->where('professional_id', $request->user()->clinicalPracticeId()))
            ->with(['patient', 'therapySession']);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['patient_id'])) {
            $query->where('patient_id', $validated['patient_id']);
        }

        $search = isset($validated['q']) ? trim((string) $validated['q']) : '';
        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->whereHas('patient', fn ($q) => $q->where('name', 'like', $term));
        }

        if (! empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $payments = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $patients = Patient::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->orderBy('name')
            ->get(['id', 'name']);

        $filtersActive = collect($validated)->contains(fn ($v) => $v !== null && $v !== '');

        return view('payments.index', compact('payments', 'patients', 'filtersActive'));
    }

    public function create(Request $request): View
    {
        $patients = Patient::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->orderBy('name')
            ->get();

        $therapySessions = TherapySession::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->with('patient')
            ->orderByDesc('session_date')
            ->orderByDesc('session_time')
            ->limit(30)
            ->get();

        $prefillSessionId = $request->integer('therapy_session_id') ?: null;
        $prefillPatientId = $request->integer('patient_id') ?: null;
        $prefillSessionParticipantId = $request->integer('session_participant_id') ?: null;
        $defaultAmount = (float) config('payment.default_session_amount', 150);
        $prefillSession = null;
        $sessionBillableParticipants = collect();
        $useParticipantBilling = false;

        if ($prefillSessionId) {
            $prefillSession = TherapySession::query()
                ->where('professional_id', $request->user()->clinicalPracticeId())
                ->with(['patient', 'participants'])
                ->find($prefillSessionId);

            if ($prefillSession !== null) {
                if (! $therapySessions->contains('id', $prefillSession->id)) {
                    $therapySessions->prepend($prefillSession);
                }

                $sessionBillableParticipants = $this->participants->billableParticipants($prefillSession);
                $useParticipantBilling = $sessionBillableParticipants->isNotEmpty()
                    && (
                        $prefillSession->patient_id === null
                        || in_array($prefillSession->session_mode, [
                            SessionMode::WithObserver,
                            SessionMode::Family,
                            SessionMode::Group,
                        ], true)
                    );

                if (! $useParticipantBilling && ! $prefillPatientId && $prefillSession->patient_id) {
                    $prefillPatientId = $prefillSession->patient_id;
                }

                if ($useParticipantBilling && ! $prefillSessionParticipantId) {
                    $prefillSessionParticipantId = $sessionBillableParticipants->first()?->id;
                }
            }
        }

        return view('payments.create', compact(
            'patients',
            'therapySessions',
            'prefillSessionId',
            'prefillPatientId',
            'prefillSessionParticipantId',
            'prefillSession',
            'sessionBillableParticipants',
            'useParticipantBilling',
            'defaultAmount',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $practiceId = $request->user()->clinicalPracticeId();

        $validated = $request->validate([
            'patient_id' => [
                'nullable',
                Rule::exists('patients', 'id')->where('professional_id', $practiceId),
            ],
            'session_participant_id' => ['nullable', 'integer'],
            'therapy_session_id' => [
                'nullable',
                Rule::exists('therapy_sessions', 'id')->where('professional_id', $practiceId),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! empty($validated['session_participant_id'])) {
            $participant = SessionParticipant::query()
                ->with('therapySession')
                ->find($validated['session_participant_id']);

            if ($participant === null) {
                throw ValidationException::withMessages([
                    'session_participant_id' => __('Participante inválido.'),
                ]);
            }

            $session = $participant->therapySession;
            if ($session === null || (int) $session->professional_id !== (int) $practiceId) {
                throw ValidationException::withMessages([
                    'session_participant_id' => __('Participante não pertence à sua agenda.'),
                ]);
            }

            if (! empty($validated['therapy_session_id']) && (int) $validated['therapy_session_id'] !== (int) $session->id) {
                throw ValidationException::withMessages([
                    'therapy_session_id' => __('A sessão selecionada não corresponde ao participante.'),
                ]);
            }

            $validated['therapy_session_id'] = $session->id;
            $validated['patient_id'] = $this->payments
                ->resolvePatientForParticipant($participant, $request->user())
                ->id;
        }

        if (empty($validated['patient_id'])) {
            throw ValidationException::withMessages([
                'patient_id' => __('Selecione quem será cobrado.'),
            ]);
        }

        $payment = $this->payments->create($validated, $request->user());

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', 'Pagamento registrado.');
    }

    public function show(Payment $payment): View
    {
        $payment->load(['patient', 'therapySession']);

        return view('payments.show', compact('payment'));
    }

    public function confirmManual(Payment $payment): RedirectResponse
    {
        $this->authorize('confirmManual', $payment);

        try {
            $this->payments->confirmManualPayment($payment);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', __('Pagamento PIX confirmado com sucesso.'));
    }

    public function edit(Payment $payment): View
    {
        $payment->load(['patient', 'therapySession']);

        return view('payments.edit', compact('payment'));
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->payments->update($payment, $validated);

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', 'Pagamento atualizado.');
    }

    public function quickUpdate(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['sometimes', 'nullable', Rule::enum(PaymentMethod::class)],
            'clear_payment_method' => ['sometimes', 'boolean'],
        ]);

        if (! array_key_exists('status', $validated)
            && ! array_key_exists('payment_method', $validated)
            && ! $request->boolean('clear_payment_method')) {
            throw ValidationException::withMessages([
                'status' => __('Selecione um estado ou forma de pagamento.'),
            ]);
        }

        $payload = [
            'amount' => $payment->amount,
            'status' => $validated['status'] ?? $payment->status->value,
            'notes' => $payment->notes,
        ];

        if ($request->boolean('clear_payment_method')) {
            $payload['payment_method'] = null;
        } elseif (array_key_exists('payment_method', $validated)) {
            $payload['payment_method'] = $validated['payment_method'];
        } else {
            $payload['payment_method'] = $payment->payment_method?->value;
        }

        $this->payments->update($payment, $payload);

        $message = array_key_exists('status', $validated)
            ? __('Estado do pagamento atualizado.')
            : __('Forma de pagamento atualizada.');

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', $message);
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()
            ->route('payments.index')
            ->with('status', 'Pagamento removido.');
    }
}
