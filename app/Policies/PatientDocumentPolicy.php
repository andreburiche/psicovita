<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
use App\Support\Permissions;

class PatientDocumentPolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permissions::SOLICITACOES_VISUALIZAR)
            && $this->ownsPatient($user, $patient);
    }

    public function create(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permissions::SOLICITACOES_ANEXAR)
            && $this->ownsPatient($user, $patient);
    }

    public function delete(User $user, PatientDocument $patientDocument): bool
    {
        return $user->hasPermission(Permissions::SOLICITACOES_ANEXAR)
            && $this->ownsPatient($user, $patientDocument->patient);
    }

    private function ownsPatient(User $user, Patient $patient): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isProfessional() && (int) $patient->professional_id === $user->clinicalPracticeId();
    }
}
