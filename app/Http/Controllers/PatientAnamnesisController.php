<?php

namespace App\Http\Controllers;

use App\Models\AnamnesisForm;
use App\Models\Patient;
use App\Services\PatientAnamnesisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientAnamnesisController extends Controller
{
    public function __construct(
        private readonly PatientAnamnesisService $patientAnamnesisService
    ) {}

    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('update', $patient);

        $form = AnamnesisForm::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->whereKey($request->input('anamnesis_form_id'))
            ->with('questions')
            ->firstOrFail();

        $rules = $this->patientAnamnesisService->validationRules($form, $request->user());
        $validated = $request->validate($rules);
        $answers = $this->patientAnamnesisService->normalizeAnswers($form, $validated['answers'] ?? []);

        $this->patientAnamnesisService->upsert($patient, $form, $request->user(), $answers);

        return redirect()
            ->route('patients.show', [
                'patient' => $patient,
                'anamnesis_form_id' => $form->id,
            ])
            ->with('status', __('Anamnese salva.'));
    }
}
