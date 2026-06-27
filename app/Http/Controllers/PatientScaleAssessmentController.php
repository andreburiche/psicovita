<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalScaleType;
use App\Http\Requests\StorePatientScaleAssessmentRequest;
use App\Models\Patient;
use App\Models\PatientScaleAssessment;
use App\Services\PatientScaleAssessmentService;
use App\Support\AuditTrail;
use App\Support\ClinicalScaleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientScaleAssessmentController extends Controller
{
    public function __construct(
        private readonly PatientScaleAssessmentService $service,
    ) {}

    public function create(Request $request, Patient $patient, string $scale): View
    {
        $scaleType = ClinicalScaleType::tryFromRoute($scale);
        abort_if($scaleType === null, 404);

        $this->authorize('create', [PatientScaleAssessment::class, $patient]);

        $latestForScale = PatientScaleAssessment::query()
            ->where('patient_id', $patient->id)
            ->where('scale_type', $scaleType)
            ->orderByDesc('assessed_at')
            ->orderByDesc('id')
            ->first();

        return view('patient-scale-assessments.create', [
            'patient' => $patient,
            'scaleType' => $scaleType,
            'definition' => ClinicalScaleCatalog::definition($scaleType),
            'questions' => ClinicalScaleCatalog::questions($scaleType),
            'options' => ClinicalScaleCatalog::options($scaleType),
            'latestForScale' => $latestForScale,
        ]);
    }

    public function store(StorePatientScaleAssessmentRequest $request, Patient $patient): RedirectResponse
    {
        $this->authorize('create', [PatientScaleAssessment::class, $patient]);

        $scale = ClinicalScaleType::from((string) $request->input('scale_type'));

        $assessment = $this->service->create(
            $patient,
            $request->user(),
            $scale,
            (string) $request->input('assessed_at'),
            $request->normalizedAnswers(),
            $request->input('notes'),
        );

        AuditTrail::entity('create', 'patient_scale_assessments', $assessment, null, $request->user());

        return redirect()
            ->route('patients.show', ['patient' => $patient, 'tab' => 'assessments'])
            ->with('status', __(':scale aplicada com sucesso. Pontuação: :score.', [
                'scale' => $scale->label(),
                'score' => $assessment->total_score,
            ]));
    }

    public function destroy(Request $request, Patient $patient, PatientScaleAssessment $scaleAssessment): RedirectResponse
    {
        abort_unless((int) $scaleAssessment->patient_id === (int) $patient->id, 404);

        $this->authorize('delete', $scaleAssessment);

        AuditTrail::entity('delete', 'patient_scale_assessments', $scaleAssessment, null, $request->user());

        $scaleAssessment->delete();

        return redirect()
            ->route('patients.show', ['patient' => $patient, 'tab' => 'assessments'])
            ->with('status', __('Avaliação removida.'));
    }
}
