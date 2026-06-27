<?php

namespace App\Http\Controllers;

use App\Enums\TherapeuticGoalStatus;
use App\Http\Requests\StorePatientTherapeuticGoalRequest;
use App\Models\Patient;
use App\Models\PatientScaleAssessment;
use App\Models\PatientTherapeuticGoal;
use App\Support\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientTherapeuticGoalController extends Controller
{
    public function store(StorePatientTherapeuticGoalRequest $request, Patient $patient): RedirectResponse
    {
        $this->authorize('manageGoals', [PatientScaleAssessment::class, $patient]);

        $status = TherapeuticGoalStatus::from((string) $request->input('status'));

        $goal = PatientTherapeuticGoal::query()->create([
            'patient_id' => $patient->id,
            'professional_id' => $request->user()->clinicalPracticeId(),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'status' => $status,
            'progress_percent' => (int) $request->input('progress_percent'),
            'target_date' => $request->input('target_date'),
            'achieved_at' => $status === TherapeuticGoalStatus::Achieved ? now() : null,
        ]);

        AuditTrail::entity('create', 'patient_therapeutic_goals', $goal, null, $request->user());

        return redirect()
            ->route('patients.show', ['patient' => $patient, 'tab' => 'assessments'])
            ->with('status', __('Objetivo terapêutico registado.'));
    }

    public function update(StorePatientTherapeuticGoalRequest $request, Patient $patient, PatientTherapeuticGoal $therapeuticGoal): RedirectResponse
    {
        abort_unless((int) $therapeuticGoal->patient_id === (int) $patient->id, 404);

        $this->authorize('deleteGoal', $therapeuticGoal);

        $status = TherapeuticGoalStatus::from((string) $request->input('status'));

        $therapeuticGoal->fill([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'status' => $status,
            'progress_percent' => (int) $request->input('progress_percent'),
            'target_date' => $request->input('target_date'),
            'achieved_at' => $status === TherapeuticGoalStatus::Achieved
                ? ($therapeuticGoal->achieved_at ?? now())
                : null,
        ])->save();

        AuditTrail::entity('update', 'patient_therapeutic_goals', $therapeuticGoal, null, $request->user());

        return redirect()
            ->route('patients.show', ['patient' => $patient, 'tab' => 'assessments'])
            ->with('status', __('Objetivo atualizado.'));
    }

    public function destroy(Request $request, Patient $patient, PatientTherapeuticGoal $therapeuticGoal): RedirectResponse
    {
        abort_unless((int) $therapeuticGoal->patient_id === (int) $patient->id, 404);

        $this->authorize('deleteGoal', $therapeuticGoal);

        AuditTrail::entity('delete', 'patient_therapeutic_goals', $therapeuticGoal, null, $request->user());

        $therapeuticGoal->delete();

        return redirect()
            ->route('patients.show', ['patient' => $patient, 'tab' => 'assessments'])
            ->with('status', __('Objetivo removido.'));
    }
}
