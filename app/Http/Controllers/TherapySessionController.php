<?php

namespace App\Http\Controllers;

use App\Enums\SessionMode;
use App\Enums\SessionParticipantRole;
use App\Enums\TherapySessionStatus;
use App\Enums\TherapySessionType;
use App\Models\Patient;
use App\Models\SessionParticipant;
use App\Models\TherapySession;
use App\Models\User;
use App\Services\ScheduleConflictService;
use App\Services\SessionBillingService;
use App\Services\SessionParticipantService;
use App\Services\SubscriptionService;
use App\Services\TherapySessionReportService;
use App\Support\MonthGridCalendar;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TherapySessionController extends Controller
{
    public function __construct(
        private readonly ScheduleConflictService $scheduleConflict,
        private readonly TherapySessionReportService $reportService,
        private readonly SessionParticipantService $participantService,
        private readonly SessionBillingService $billing,
    ) {
        $this->authorizeResource(TherapySession::class, 'therapy_session');
    }

    public function index(Request $request): View
    {
        $filters = $this->reportService->parseFilters($request);

        validator($filters, [
            'status' => ['nullable', Rule::enum(TherapySessionStatus::class)],
            'type' => ['nullable', Rule::enum(TherapySessionType::class)],
            'patient_id' => ['nullable', 'integer', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
            'q' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ])->validate();

        $calendar = MonthGridCalendar::forProfessional($request->user()->clinicalPracticeId(), $request->query('month'), $filters);

        $perPage = in_array((int) $request->query('per_page', 15), [10, 15, 25, 50], true)
            ? (int) $request->query('per_page')
            : 15;

        $sessionsQuery = $this->reportService
            ->applyFilters(
                TherapySession::query()->where('professional_id', $request->user()->clinicalPracticeId()),
                $filters
            )
            ->with(['patient', 'payments', 'participants'])
            ->orderByDesc('session_date')
            ->orderByDesc('session_time');

        if (! $filters['from'] && ! $filters['to']) {
            $month = $calendar['month'];
            $sessionsQuery->whereBetween('session_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ]);
        }

        $sessions = $sessionsQuery->paginate($perPage)->withQueryString();

        $patients = Patient::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('therapy-sessions.index', [
            'sessions' => $sessions,
            'month' => $calendar['month'],
            'weeks' => $calendar['weeks'],
            'filters' => $filters,
            'filtersActive' => $this->reportService->filtersActive($filters),
            'stats' => $this->reportService->computeStats($request->user()->clinicalPracticeId(), $filters, $calendar['month']),
            'patients' => $patients,
        ]);
    }

    public function create(Request $request): View
    {
        $patients = Patient::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->orderBy('name')
            ->get();

        $defaultSessionDate = now()->format('Y-m-d');
        $dateQuery = $request->query('date');
        if (is_string($dateQuery) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateQuery)) {
            try {
                $defaultSessionDate = Carbon::parse($dateQuery)->format('Y-m-d');
            } catch (\Throwable) {
                // mantém default
            }
        }

        $returnMonth = null;
        $monthQuery = $request->query('month');
        if (is_string($monthQuery) && preg_match('/^\d{4}-\d{2}$/', $monthQuery)) {
            $returnMonth = $monthQuery;
        }

        return view('therapy-sessions.create', [
            'patients' => $patients,
            'professionals' => $this->participantService->listPracticeProfessionals($request->user()),
            'defaultSessionDate' => old('session_date', $defaultSessionDate),
            'returnMonth' => $returnMonth,
            'defaultPatientId' => old('patient_id', $request->integer('patient_id') ?: null),
            'defaultPaymentAmount' => (float) config('payment.default_session_amount', 150),
            'autoChargeDefault' => (bool) config('payment.auto_charge_on_session_created', true),
            'patientsNameMap' => $patients->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(fn () => SessionMode::tryFrom((string) request()->input('session_mode', SessionMode::Individual->value)) === SessionMode::Individual),
                Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId()),
            ],
            'session_date' => ['required', 'date'],
            'session_time' => ['required', 'date_format:H:i'],
            'status' => ['required', Rule::enum(TherapySessionStatus::class)],
            'type' => ['required', Rule::enum(TherapySessionType::class)],
            'session_mode' => ['nullable', Rule::enum(SessionMode::class)],
            'observer_source' => ['nullable', Rule::in(['professional', 'external'])],
            'observer_professional_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', \App\Enums\UserRole::Professional->value),
            ],
            'observer_name' => ['nullable', 'string', 'max:120'],
            'observer_email' => ['nullable', 'email', 'max:255'],
            'family_patient_ids' => ['nullable', 'array', 'max:12'],
            'family_patient_ids.*' => ['integer', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
            'session_observers' => ['nullable', 'array', 'max:5'],
            'session_observers.*.source' => ['nullable', Rule::in(['professional', 'external'])],
            'session_observers.*.professional_id' => ['nullable', 'integer'],
            'session_observers.*.name' => ['nullable', 'string', 'max:120'],
            'session_observers.*.email' => ['nullable', 'email', 'max:255'],
            'family_guest_name' => ['nullable', 'array', 'max:5'],
            'family_guest_name.*' => ['nullable', 'string', 'max:120'],
            'family_guest_email' => ['nullable', 'array', 'max:5'],
            'family_guest_email.*' => ['nullable', 'email', 'max:255'],
            'group_patient_ids' => ['nullable', 'array', 'max:12'],
            'group_patient_ids.*' => ['integer', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
            'billing_patient_id' => [
                'nullable',
                'integer',
                Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId()),
            ],
            'generate_payment' => ['nullable', 'boolean'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $status = $validated['status'] instanceof TherapySessionStatus
            ? $validated['status']
            : TherapySessionStatus::from((string) $validated['status']);

        $type = $validated['type'] instanceof TherapySessionType
            ? $validated['type']
            : TherapySessionType::from((string) $validated['type']);

        $sessionMode = $this->resolveSessionMode($validated, $type);

        if ($sessionMode === SessionMode::WithObserver) {
            $sessionObservers = $this->parseSessionObserversFromRequest($request);
            if ($sessionObservers === []) {
                throw ValidationException::withMessages([
                    'session_observers' => __('Adicione pelo menos um observador na lista antes de salvar.'),
                ]);
            }
        }

        $familyGuests = $this->parseFamilyGuestsFromRequest($request);
        $familyPatientIds = $this->parseFamilyPatientIdsFromRequest($request);

        if ($sessionMode === SessionMode::Family) {
            if ($familyGuests === [] && $familyPatientIds === []) {
                throw ValidationException::withMessages([
                    'family_patient_ids' => __('Selecione utente(s) do sistema ou adicione convidado(s) externo(s).'),
                ]);
            }
        }

        $groupPatientIds = $this->parseGroupPatientIdsFromRequest($request);

        if ($sessionMode === SessionMode::Group) {
            if (count($groupPatientIds) < 2) {
                throw ValidationException::withMessages([
                    'group_patient_ids' => __('Selecione pelo menos dois utentes para a sessão de grupo.'),
                ]);
            }

            $validated['patient_id'] = $this->resolveBillingPatientId(
                $request->integer('billing_patient_id') ?: null,
                $groupPatientIds,
            );
        } elseif ($sessionMode === SessionMode::WithObserver) {
            $validated['patient_id'] = null;
        } elseif ($sessionMode === SessionMode::Family) {
            if (empty($validated['patient_id']) && $familyPatientIds !== []) {
                $validated['patient_id'] = $familyPatientIds[0];
            } elseif (empty($validated['patient_id'])) {
                $validated['patient_id'] = null;
            }

            $familyBillingIds = array_values(array_unique(array_filter(array_merge(
                $familyPatientIds,
                ! empty($validated['patient_id']) ? [(int) $validated['patient_id']] : [],
            ))));

            if ($familyBillingIds !== []) {
                $validated['patient_id'] = $this->resolveBillingPatientId(
                    $request->integer('billing_patient_id') ?: null,
                    $familyBillingIds,
                );
            }
        }

        if ($this->scheduleConflict->hasConflict(
            $request->user()->clinicalPracticeId(),
            (string) $validated['session_date'],
            (string) $validated['session_time'],
            null,
            $status,
        )) {
            throw ValidationException::withMessages([
                'session_time' => __('Horário indisponível: conflito com outra sessão ou bloqueio da agenda.'),
            ]);
        }

        $validated['professional_id'] = $request->user()->clinicalPracticeId();
        $validated['session_mode'] = $sessionMode->value;
        unset($validated['observer_name'], $validated['observer_email'], $validated['family_guest_name'], $validated['family_guest_email']);

        $generatePayment = $request->has('generate_payment')
            ? $request->boolean('generate_payment')
            : (bool) config('payment.auto_charge_on_session_created', true);
        $paymentAmount = $request->filled('payment_amount')
            ? round((float) $request->input('payment_amount'), 2)
            : null;

        $session = TherapySession::query()->make($validated);
        $session->skipAutoPayment = ! $generatePayment
            || $status === TherapySessionStatus::Cancelled
            || ! $session->patient_id;
        $session->forceAutoPayment = $generatePayment && (bool) $session->patient_id;

        if ($paymentAmount !== null && $generatePayment && $session->patient_id) {
            $session->paymentAmountOverride = $paymentAmount;
        }

        $session->save();

        if ($sessionMode === SessionMode::WithObserver) {
            $this->participantService->ensureObserversFromRequest(
                $session,
                $this->parseSessionObserversFromRequest($request),
                $request->user(),
            );
        }

        if ($sessionMode === SessionMode::Family) {
            $this->participantService->ensureFamilyParticipantsFromRequest(
                $session,
                $familyPatientIds,
                $familyGuests,
            );
        }

        if ($sessionMode === SessionMode::Group) {
            $this->participantService->ensureGroupMembersFromRequest($session, $groupPatientIds);
        }

        return redirect()
            ->route('therapy-sessions.show', $session)
            ->with('status', 'Sessão agendada.');
    }

    public function show(TherapySession $therapySession): View
    {
        $therapySession->load('patient', 'payments.patient', 'videoCall', 'participants');

        $canUseVideoConference = app(SubscriptionService::class)->canUseFeature(auth()->user(), 'use_ai');
        $observers = $this->participantService->observerParticipants($therapySession);
        $observer = $observers->first();
        $familyGuests = $this->participantService->guestParticipants($therapySession);
        $groupMembers = $therapySession->session_mode === SessionMode::Group
            ? $this->participantService->patientParticipants($therapySession)
            : collect();
        $billingOverview = $this->billing->overview($therapySession);

        return view('therapy-sessions.show', [
            'session' => $therapySession,
            'canUseVideoConference' => $canUseVideoConference,
            'observer' => $observer,
            'observers' => $observers,
            'familyGuests' => $familyGuests,
            'groupMembers' => $groupMembers,
            'billingOverview' => $billingOverview,
        ]);
    }

    public function edit(Request $request, TherapySession $therapySession): View
    {
        $therapySession->load('patient');

        $patients = Patient::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->orderBy('name')
            ->get();

        return view('therapy-sessions.edit', [
            'session' => $therapySession,
            'patients' => $patients,
        ]);
    }

    public function update(Request $request, TherapySession $therapySession): RedirectResponse
    {
        $requiresPatient = ($therapySession->session_mode ?? SessionMode::Individual) === SessionMode::Individual;

        $validated = $request->validate([
            'patient_id' => [
                'nullable',
                'integer',
                Rule::requiredIf($requiresPatient),
                Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId()),
            ],
            'session_date' => ['required', 'date'],
            'session_time' => ['required', 'date_format:H:i'],
            'status' => ['required', Rule::enum(TherapySessionStatus::class)],
            'type' => ['required', Rule::enum(TherapySessionType::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (! $requiresPatient) {
            unset($validated['patient_id']);
        }

        $status = $validated['status'] instanceof TherapySessionStatus
            ? $validated['status']
            : TherapySessionStatus::from((string) $validated['status']);

        if ($this->scheduleConflict->hasConflict(
            $request->user()->clinicalPracticeId(),
            (string) $validated['session_date'],
            (string) $validated['session_time'],
            $therapySession->id,
            $status,
        )) {
            throw ValidationException::withMessages([
                'session_time' => __('Horário indisponível: conflito com outra sessão ou bloqueio da agenda.'),
            ]);
        }

        $therapySession->update($validated);

        return redirect()
            ->route('therapy-sessions.show', $therapySession)
            ->with('status', 'Sessão atualizada.');
    }

    public function updateStatus(Request $request, TherapySession $therapySession): RedirectResponse
    {
        $this->authorize('update', $therapySession);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(TherapySessionStatus::class)],
        ]);

        $status = $validated['status'] instanceof TherapySessionStatus
            ? $validated['status']
            : TherapySessionStatus::from((string) $validated['status']);

        if (! in_array($status, [TherapySessionStatus::Completed, TherapySessionStatus::Cancelled], true)) {
            return redirect()
                ->back()
                ->withErrors(['status' => __('Selecione concluída ou cancelada.')]);
        }

        $therapySession->update(['status' => $status]);

        $message = match ($status) {
            TherapySessionStatus::Completed => __('Sessão marcada como concluída.'),
            TherapySessionStatus::Cancelled => __('Sessão marcada como cancelada.'),
            default => __('Sessão atualizada.'),
        };

        return redirect()
            ->back()
            ->with('status', $message);
    }

    public function destroy(TherapySession $therapySession): RedirectResponse
    {
        $therapySession->delete();

        return redirect()
            ->route('therapy-sessions.index')
            ->with('status', 'Sessão excluída.');
    }

    public function resendObserverInvite(TherapySession $therapySession): RedirectResponse
    {
        $this->authorize('update', $therapySession);

        $observer = $this->participantService->observerParticipant($therapySession);
        if (! $observer) {
            return back()->withErrors(['observer' => __('Nenhum observador configurado nesta sessão.')]);
        }

        $this->participantService->sendObserverInvite($observer);

        return back()->with('status', __('Convite reenviado ao observador.'));
    }

    public function resendParticipantInvite(TherapySession $therapySession, SessionParticipant $participant): RedirectResponse
    {
        $this->authorize('update', $therapySession);

        if ((int) $participant->therapy_session_id !== (int) $therapySession->id) {
            abort(404);
        }

        if ($participant->role === SessionParticipantRole::Patient) {
            if ($therapySession->session_mode !== SessionMode::Group) {
                return back()->withErrors(['participant' => __('Este participante não recebe convite por e-mail.')]);
            }
        } elseif (! in_array($participant->role, [SessionParticipantRole::Observer, SessionParticipantRole::Guest], true)) {
            return back()->withErrors(['participant' => __('Este participante não recebe convite por e-mail.')]);
        }

        $this->participantService->sendParticipantInvite($participant);

        return back()->with('status', __('Convite reenviado.'));
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveSessionMode(array $validated, TherapySessionType $type): SessionMode
    {
        if ($type !== TherapySessionType::Online) {
            return SessionMode::Individual;
        }

        $raw = $validated['session_mode'] ?? SessionMode::Individual->value;

        return $raw instanceof SessionMode
            ? $raw
            : SessionMode::from((string) $raw);
    }

    private function requiresPrimaryPatient(SessionMode $mode): bool
    {
        return $mode === SessionMode::Individual;
    }

    /**
     * @return list<array{source: string, professional_id?: int, name?: string, email?: string}>
     */
    private function parseSessionObserversFromRequest(Request $request): array
    {
        $raw = $request->input('session_observers', []);
        if (! is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $source = (string) ($row['source'] ?? 'external');
            if ($source === 'professional' && ! empty($row['professional_id'])) {
                $items[] = [
                    'source' => 'professional',
                    'professional_id' => (int) $row['professional_id'],
                ];
            } elseif ($source === 'external') {
                $name = trim((string) ($row['name'] ?? ''));
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                if ($name !== '' && $email !== '') {
                    $items[] = [
                        'source' => 'external',
                        'name' => $name,
                        'email' => $email,
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * @return list<array{name: string, email: string}>
     */
    private function parseFamilyGuestsFromRequest(Request $request): array
    {
        $names = $request->input('family_guest_name', []);
        $emails = $request->input('family_guest_email', []);
        $guests = [];

        if (! is_array($names) || ! is_array($emails)) {
            return [];
        }

        $count = max(count($names), count($emails));
        for ($i = 0; $i < $count; $i++) {
            $name = trim((string) ($names[$i] ?? ''));
            $email = strtolower(trim((string) ($emails[$i] ?? '')));

            if ($name === '' && $email === '') {
                continue;
            }

            if ($name === '' || $email === '') {
                throw ValidationException::withMessages([
                    "family_guest_name.$i" => __('Preencha nome e e-mail de cada convidado.'),
                ]);
            }

            $guests[] = ['name' => $name, 'email' => $email];
        }

        return $guests;
    }

    /**
     * @return list<int>
     */
    private function parseFamilyPatientIdsFromRequest(Request $request): array
    {
        $raw = $request->input('family_patient_ids', []);
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $id) {
            $id = (int) $id;
            if ($id > 0 && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    private function parseGroupPatientIdsFromRequest(Request $request): array
    {
        $raw = $request->input('group_patient_ids', []);
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $id) {
            $id = (int) $id;
            if ($id > 0 && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param  list<int>  $allowedPatientIds
     */
    private function resolveBillingPatientId(?int $billingPatientId, array $allowedPatientIds): int
    {
        if ($billingPatientId !== null && in_array($billingPatientId, $allowedPatientIds, true)) {
            return $billingPatientId;
        }

        return $allowedPatientIds[0];
    }
}
