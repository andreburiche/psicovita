<?php

namespace App\Support;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class PatientAccountResolver
{
    /**
     * Fichas de paciente vinculadas à conta pelo e-mail normalizado.
     *
     * @return Collection<int, Patient>
     */
    public function patientsForUser(User $user): Collection
    {
        $email = $user->normalizedEmail();
        if ($email === '') {
            return new Collection;
        }

        return Patient::query()
            ->where('email_hash', ContactHasher::emailHash($email))
            ->with('professional:id,name,email')
            ->orderBy('id')
            ->get();
    }

    public function resolvePatientForUser(User $user, ?int $patientId): ?Patient
    {
        $patients = $this->patientsForUser($user);

        if ($patients->isEmpty()) {
            return null;
        }

        if ($patientId !== null) {
            return $patients->firstWhere('id', $patientId);
        }

        return $patients->count() === 1 ? $patients->first() : null;
    }
}
