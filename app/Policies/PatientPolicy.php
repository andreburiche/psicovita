<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\Response;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProfessional();
    }

    public function view(User $user, Patient $patient): bool
    {
        return $patient->professional_id === $user->clinicalPracticeId();
    }

    public function create(User $user): Response|bool
    {
        if (! $user->isProfessional()) {
            return false;
        }

        $subscriptions = app(SubscriptionService::class);

        if (! $subscriptions->canUseFeature($user, 'create_patient')) {
            return Response::deny(__('A sua assinatura expirou ou não inclui esta funcionalidade. Consulte o seu plano em Configurações.'));
        }

        if (! $subscriptions->canAddPatient($user)) {
            return Response::deny(__('O seu plano permite até :count pacientes. Actualize a assinatura para adicionar mais.', [
                'count' => $subscriptions->patientLimit($user),
            ]));
        }

        return true;
    }

    public function update(User $user, Patient $patient): bool
    {
        return $patient->professional_id === $user->clinicalPracticeId();
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $patient->professional_id === $user->clinicalPracticeId();
    }

    public function restore(User $user, Patient $patient): bool
    {
        return false;
    }

    public function forceDelete(User $user, Patient $patient): bool
    {
        return false;
    }

}
