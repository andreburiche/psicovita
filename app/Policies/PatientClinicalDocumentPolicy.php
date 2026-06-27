<?php

namespace App\Policies;

use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\PatientClinicalDocument;
use App\Models\User;

class PatientClinicalDocumentPolicy
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

    public function view(User $user, PatientClinicalDocument $document): bool
    {
        return $this->ownsPatient($user, $document->patient)
            && (int) $document->professional_id === $user->clinicalPracticeId();
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
