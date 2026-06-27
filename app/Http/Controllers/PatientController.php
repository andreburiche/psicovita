<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\AnamnesisForm;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Support\AvatarStyleOptions;
use App\Support\CpfHasher;
use App\Models\PatientAnamnesis;
use App\Models\DocumentRequest;
use App\Models\PatientClinicalDocument;
use App\Models\PatientScaleAssessment;
use App\Services\DocumentRequestService;
use App\Services\PatientDocumentService;
use App\Services\PatientPortalProvisioningService;
use App\Services\PatientScaleAssessmentService;
use App\Services\PatientService;
use App\Services\SubscriptionService;
use App\Services\UserAvatarService;
use App\Rules\PatientEmailNotUsedByProfessional;
use App\Support\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function __construct(
        private readonly PatientService $patientService,
        private readonly DocumentRequestService $documentRequestService,
        private readonly PatientDocumentService $patientDocumentService,
        private readonly UserAvatarService $avatars,
        private readonly SubscriptionService $subscriptions,
        private readonly PatientScaleAssessmentService $scaleAssessments,
        private readonly PatientPortalProvisioningService $portalProvisioning,
    ) {
        $this->authorizeResource(Patient::class, 'patient');
    }

    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $patients = $this->patientService->paginateForProfessional(
            $request->user(),
            $request->string('q')->toString() ?: null,
            $perPage,
        );

        $patientQuota = $this->subscriptions->patientQuotaContext($request->user());

        return view('patients.index', compact('patients', 'patientQuota'));
    }

    public function create(): View
    {
        return view('patients.create', [
            'patientQuota' => $this->subscriptions->patientQuotaContext(request()->user()),
            'portalContext' => $this->portalProvisioning->statusContext(new Patient),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            $this->patientFormRules($request),
            $this->patientFormMessages(),
        );

        $patient = $this->patientService->create($request->user(), $validated);

        $this->applyAvatarFromRequest($request, $patient);

        AuditTrail::entity('create', 'patients', $patient, null, $request->user());

        $statusMessage = __('Paciente cadastrado.');

        if ($this->portalProvisioningRequested($request)) {
            try {
                $sendEmail = $request->boolean('send_portal_invite_email', true);
                $sendWhatsApp = $request->boolean('send_portal_invite_whatsapp', false);
                $this->portalProvisioning->provision(
                    $patient,
                    $request->user(),
                    $sendEmail,
                    $sendWhatsApp,
                );
                $statusMessage = __('Paciente cadastrado. :detail', [
                    'detail' => $this->portalProvisioning->inviteSentMessage($sendEmail, $sendWhatsApp, $patient),
                ]);
            } catch (\InvalidArgumentException $e) {
                return redirect()
                    ->route('patients.show', $patient)
                    ->with('warning', $e->getMessage());
            }
        }

        return redirect()
            ->route('patients.show', $patient)
            ->with('status', $statusMessage);
    }

    public function show(Request $request, Patient $patient): View
    {
        $patient->load(['therapySessions' => fn ($q) => $q->latest('session_date')->latest('session_time')->limit(20)]);

        $anamnesisForms = AnamnesisForm::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->orderBy('title')
            ->get();

        $requestedFormId = $request->integer('anamnesis_form_id') ?: null;
        $selectedForm = $requestedFormId
            ? $anamnesisForms->firstWhere('id', $requestedFormId)
            : null;
        $selectedForm ??= $anamnesisForms->first();
        $selectedForm?->load('questions');

        $anamnesisAnswers = [];
        if ($selectedForm) {
            $record = PatientAnamnesis::query()
                ->where('patient_id', $patient->id)
                ->where('anamnesis_form_id', $selectedForm->id)
                ->first();
            $anamnesisAnswers = is_array($record?->answers) ? $record->answers : [];
        }

        AuditTrail::entity('view', 'patients', $patient, null, $request->user());

        $activeTab = $request->string('tab')->toString() ?: 'overview';
        $documentRequests = collect();
        $patientDocuments = collect();
        $clinicalDocuments = collect();
        if ($request->user()->can('viewAny', [DocumentRequest::class, $patient])) {
            $documentRequests = $this->documentRequestService->listForPatient($patient);
            $patientDocuments = $this->patientDocumentService->listForPatient($patient);
        }
        if ($request->user()->can('viewAny', [PatientClinicalDocument::class, $patient])) {
            $clinicalDocuments = $patient->clinicalDocuments()
                ->with('professional')
                ->limit(20)
                ->get();
        }

        $clinicalRecords = null;
        $clinicalRecordsCount = 0;
        $latestClinicalRecord = null;

        if ($request->user()->can('viewAny', ClinicalRecord::class)) {
            $recordsQuery = ClinicalRecord::query()
                ->where('patient_id', $patient->id)
                ->where('professional_id', $request->user()->clinicalPracticeId());

            $clinicalRecordsCount = (clone $recordsQuery)->count();
            $latestClinicalRecord = (clone $recordsQuery)->latest()->first();

            if ($activeTab === 'clinical-records') {
                $perPage = (int) $request->input('per_page', 10);
                if (! in_array($perPage, [10, 15, 25, 50], true)) {
                    $perPage = 10;
                }

                $clinicalRecords = (clone $recordsQuery)
                    ->latest()
                    ->paginate($perPage)
                    ->withQueryString();
            }
        }

        $payments = null;
        $paymentsCount = 0;
        $latestPayment = null;
        $paymentStats = [
            'paid_total' => 0.0,
            'pending_total' => 0.0,
            'overdue_count' => 0,
        ];

        if ($request->user()->can('viewAny', Payment::class)) {
            $paymentsQuery = Payment::query()
                ->where('patient_id', $patient->id);

            $paymentsCount = (clone $paymentsQuery)->count();
            $latestPayment = (clone $paymentsQuery)->latest()->with('therapySession')->first();

            $paymentStats = [
                'paid_total' => (float) (clone $paymentsQuery)->where('status', PaymentStatus::Paid)->sum('amount'),
                'pending_total' => (float) (clone $paymentsQuery)
                    ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Overdue])
                    ->sum('amount'),
                'overdue_count' => (clone $paymentsQuery)->where('status', PaymentStatus::Overdue)->count(),
            ];

            if ($activeTab === 'payments') {
                $perPage = (int) $request->input('per_page', 10);
                if (! in_array($perPage, [10, 15, 25, 50], true)) {
                    $perPage = 10;
                }

                $payments = (clone $paymentsQuery)
                    ->with('therapySession')
                    ->latest()
                    ->paginate($perPage)
                    ->withQueryString();
            }
        }

        $scaleAssessmentHistory = collect();
        $scaleChartData = [];
        $scaleLatest = [];
        $therapeuticGoals = collect();

        if ($request->user()->can('viewAny', [PatientScaleAssessment::class, $patient])) {
            if ($activeTab === 'assessments') {
                $scaleAssessmentHistory = $this->scaleAssessments->listForPatient($patient);
                $scaleChartData = $this->scaleAssessments->chartData($patient);
                $scaleLatest = $this->scaleAssessments->latestByScale($patient);
                $therapeuticGoals = $patient->therapeuticGoals()
                    ->where('professional_id', $request->user()->clinicalPracticeId())
                    ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'pending' THEN 1 WHEN 'achieved' THEN 2 ELSE 3 END")
                    ->orderByDesc('updated_at')
                    ->get();
            } else {
                $scaleLatest = $this->scaleAssessments->latestByScale($patient);
            }
        }

        $portalContext = $this->portalProvisioning->statusContext($patient);

        return view('patients.show', compact(
            'patient',
            'anamnesisForms',
            'selectedForm',
            'anamnesisAnswers',
            'activeTab',
            'documentRequests',
            'patientDocuments',
            'clinicalDocuments',
            'clinicalRecords',
            'clinicalRecordsCount',
            'latestClinicalRecord',
            'payments',
            'paymentsCount',
            'latestPayment',
            'paymentStats',
            'scaleAssessmentHistory',
            'scaleChartData',
            'scaleLatest',
            'therapeuticGoals',
            'portalContext',
        ));
    }

    public function edit(Patient $patient): View
    {
        return view('patients.edit', [
            'patient' => $patient,
            'portalContext' => $this->portalProvisioning->statusContext($patient),
        ]);
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validate(
            $this->patientFormRules($request, $patient),
            $this->patientFormMessages(),
        );

        $this->patientService->update($patient, $validated);

        $patient->refresh();

        $this->applyAvatarFromRequest($request, $patient);

        AuditTrail::entity('update', 'patients', $patient, null, $request->user());

        $statusMessage = __('Paciente atualizado.');

        if ($this->portalProvisioningRequested($request, $patient)) {
            try {
                $sendEmail = $request->boolean('send_portal_invite_email', true);
                $sendWhatsApp = $request->boolean('send_portal_invite_whatsapp', false);
                $this->portalProvisioning->provision(
                    $patient,
                    $request->user(),
                    $sendEmail,
                    $sendWhatsApp,
                );
                $statusMessage = __('Paciente atualizado. :detail', [
                    'detail' => $this->portalProvisioning->inviteSentMessage($sendEmail, $sendWhatsApp, $patient),
                ]);
            } catch (\InvalidArgumentException $e) {
                return redirect()
                    ->route('patients.show', $patient)
                    ->with('warning', $e->getMessage());
            }
        }

        return redirect()
            ->route('patients.show', $patient)
            ->with('status', $statusMessage);
    }

    public function resendPortalInvite(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('update', $patient);

        $portalContext = $this->portalProvisioning->statusContext($patient);

        if ($portalContext['can_provision'] ?? false) {
            $request->validate(
                ['portal_lgpd_acknowledged' => ['accepted']],
                $this->patientFormMessages(),
            );
        }

        $sendEmail = $request->boolean('send_portal_invite_email', true);
        $sendWhatsApp = $request->boolean('send_portal_invite_whatsapp', true);

        try {
            $this->portalProvisioning->resend($patient, $request->user(), $sendEmail, $sendWhatsApp);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $this->portalProvisioning->inviteSentMessage($sendEmail, $sendWhatsApp, $patient));
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        AuditTrail::entity('delete', 'patients', $patient);

        $this->patientService->delete($patient);

        return redirect()
            ->route('patients.index')
            ->with('status', 'Paciente removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function patientFormRules(Request $request, ?Patient $ignoreDuplicateCpfFor = null): array
    {
        $portalRequested = $this->portalProvisioningRequested($request, $ignoreDuplicateCpfFor);

        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                Rule::requiredIf($portalRequested),
                'nullable',
                'email',
                'max:255',
                new PatientEmailNotUsedByProfessional,
            ],
            'phone' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                if (! is_string($value) || ! is_valid_br_phone_digits($value)) {
                    $fail(__('Telefone inválido.'));
                }
            }],
            'birth_date' => ['nullable', 'date'],
            'cpf' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail) use ($request, $ignoreDuplicateCpfFor) {
                $digits = is_string($value) ? only_digits($value) : '';
                if ($digits === '') {
                    return;
                }
                if (! is_valid_cpf($digits)) {
                    $fail(__('CPF inválido.'));

                    return;
                }
                $q = Patient::query()
                    ->where('professional_id', $request->user()->clinicalPracticeId())
                    ->where('cpf_hash', CpfHasher::hash($digits));
                if ($ignoreDuplicateCpfFor) {
                    $q->whereKeyNot($ignoreDuplicateCpfFor->getKey());
                }
                if ($q->exists()) {
                    $fail(__('Este CPF já está associado a outro paciente.'));
                }
            }],
            'address_postal_code' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                if (! is_string($value) || ! is_valid_cep_digits($value)) {
                    $fail(__('CEP inválido.'));
                }
            }],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:30'],
            'address_complement' => ['nullable', 'string', 'max:120'],
            'address_district' => ['nullable', 'string', 'max:120'],
            'address_city' => ['nullable', 'string', 'max:120'],
            'address_state' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'create_portal_access' => ['sometimes', 'boolean'],
            'send_portal_invite_email' => ['sometimes', 'boolean'],
            'send_portal_invite_whatsapp' => ['sometimes', 'boolean'],
            'portal_lgpd_acknowledged' => [
                Rule::excludeIf(! $portalRequested),
                'accepted',
            ],
        ], $this->avatarFormRules());
    }

    private function portalProvisioningRequested(Request $request, ?Patient $patient = null): bool
    {
        if (! $request->has('create_portal_access') || ! $request->boolean('create_portal_access')) {
            return false;
        }

        if ($patient === null) {
            return true;
        }

        return (bool) ($this->portalProvisioning->statusContext($patient)['can_provision'] ?? false);
    }

    /**
     * @return array<string, string>
     */
    private function patientFormMessages(): array
    {
        return [
            'portal_lgpd_acknowledged.accepted' => __('Marque «Autorização do paciente» ao criar acesso ao portal.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function avatarFormRules(): array
    {
        return [
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
            'avatar_shape' => ['nullable', 'string', Rule::in(AvatarStyleOptions::SHAPES)],
            'avatar_ring' => ['nullable', 'string', Rule::in(AvatarStyleOptions::RINGS)],
            'avatar_filter' => ['nullable', 'string', Rule::in(AvatarStyleOptions::FILTERS)],
        ];
    }

    private function applyAvatarFromRequest(Request $request, Patient $patient): void
    {
        $owner = $patient->avatarOwner();
        $style = AvatarStyleOptions::resolve([
            'shape' => $request->input('avatar_shape'),
            'ring' => $request->input('avatar_ring'),
            'filter' => $request->input('avatar_filter'),
        ]);

        if ($request->boolean('remove_avatar')) {
            $this->avatars->remove($owner);
            $owner->avatar_style = AvatarStyleOptions::defaults();
            $owner->save();

            if ($owner instanceof User && $patient->avatar_path) {
                $this->avatars->remove($patient);
                $patient->avatar_style = null;
                $patient->save();
            }

            return;
        }

        if ($request->hasFile('avatar')) {
            $this->avatars->store($owner, $request->file('avatar'));
            $owner->avatar_style = $style;
            $owner->save();

            return;
        }

        if ($request->hasAny(['avatar_shape', 'avatar_ring', 'avatar_filter'])) {
            $owner->avatar_style = $style;
            $owner->save();
        }
    }
}
