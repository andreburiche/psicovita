<?php

namespace App\Http\Controllers;

use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\RecordAccessLog;
use App\Services\PatientService;
use App\Support\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClinicalRecordController extends Controller
{
    public function __construct(
        private readonly PatientService $patientService,
    ) {
        $this->authorizeResource(ClinicalRecord::class, 'clinical_record');
    }

    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $records = ClinicalRecord::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->with('patient')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $this->patientService->hydratePortalUsers(
            $records->getCollection()->pluck('patient')->filter()
        );

        return view('clinical-records.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $patients = Patient::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->orderBy('name')
            ->get();

        return view('clinical-records.create', compact('patients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
            'content' => ['required', 'string', 'max:50000'],
        ]);

        $record = ClinicalRecord::query()->create([
            ...$validated,
            'professional_id' => $request->user()->clinicalPracticeId(),
        ]);

        RecordAccessLog::query()->create([
            'user_id' => $request->user()->clinicalPracticeId(),
            'clinical_record_id' => $record->id,
            'action' => RecordAccessLog::ACTION_CREATED,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('clinical-records.show', $record)
            ->with('status', 'Registro de prontuário criado.');
    }

    public function show(Request $request, ClinicalRecord $clinicalRecord): View
    {
        $clinicalRecord->load('patient');

        RecordAccessLog::query()->create([
            'user_id' => $request->user()->clinicalPracticeId(),
            'clinical_record_id' => $clinicalRecord->id,
            'action' => RecordAccessLog::ACTION_VIEWED,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return view('clinical-records.show', ['record' => $clinicalRecord]);
    }

    public function edit(ClinicalRecord $clinicalRecord): View
    {
        $clinicalRecord->load('patient');

        return view('clinical-records.edit', ['record' => $clinicalRecord]);
    }

    public function update(Request $request, ClinicalRecord $clinicalRecord): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:50000'],
        ]);

        $clinicalRecord->update($validated);

        RecordAccessLog::query()->create([
            'user_id' => $request->user()->clinicalPracticeId(),
            'clinical_record_id' => $clinicalRecord->id,
            'action' => RecordAccessLog::ACTION_UPDATED,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('clinical-records.show', $clinicalRecord)
            ->with('status', 'Prontuário atualizado.');
    }

    public function destroy(Request $request, ClinicalRecord $clinicalRecord): RedirectResponse
    {
        RecordAccessLog::query()->create([
            'user_id' => $request->user()->clinicalPracticeId(),
            'clinical_record_id' => $clinicalRecord->id,
            'action' => RecordAccessLog::ACTION_DELETED,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        AuditTrail::entity('delete', 'clinical_records', $clinicalRecord, null, $request->user());

        $clinicalRecord->delete();

        return redirect()
            ->route('clinical-records.index')
            ->with('status', 'Registro removido.');
    }
}
