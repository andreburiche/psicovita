<?php

namespace App\Policies;

use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\PatientScaleAssessment;
use App\Models\PatientTherapeuticGoal;
use App\Models\User;

class PatientScaleAssessmentPolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        return $this->ownsPatient($user, $patient) && $user->isProfessional();
    }

    public function create(User $user, Patient $patient): bool
    {
        if (! $this->ownsPatient($user, $patient) || ! $user->isProfessional()) {
            return false;
        }

        return $user->can('create', ClinicalRecord::class) !== false;
    }

    public function delete(User $user, PatientScaleAssessment $assessment): bool
    {
        return $this->ownsPatient($user, $assessment->patient)
            && (int) $assessment->professional_id === $user->clinicalPracticeId();
    }

    public function manageGoals(User $user, Patient $patient): bool
    {
        return $this->create($user, $patient);
    }

    public function deleteGoal(User $user, PatientTherapeuticGoal $goal): bool
    {
        return $this->ownsPatient($user, $goal->patient)
            && (int) $goal->professional_id === $user->clinicalPracticeId();
    }

    private function ownsPatient(User $user, Patient $patient): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isProfessional()
            && (int) $patient->professional_id === $user->clinicalPracticeId();
    }
}
