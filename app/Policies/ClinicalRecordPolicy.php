<?php

namespace App\Policies;

use App\Models\ClinicalRecord;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\Response;

class ClinicalRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProfessional();
    }

    public function view(User $user, ClinicalRecord $clinicalRecord): bool
    {
        return $clinicalRecord->professional_id === $user->clinicalPracticeId();
    }

    public function create(User $user): Response|bool
    {
        if (! $user->isProfessional()) {
            return false;
        }

        return $this->allowFeature($user, 'create_clinical_record');
    }

    public function update(User $user, ClinicalRecord $clinicalRecord): bool
    {
        return $clinicalRecord->professional_id === $user->clinicalPracticeId();
    }

    public function delete(User $user, ClinicalRecord $clinicalRecord): bool
    {
        return $clinicalRecord->professional_id === $user->clinicalPracticeId();
    }

    public function restore(User $user, ClinicalRecord $clinicalRecord): bool
    {
        return false;
    }

    public function forceDelete(User $user, ClinicalRecord $clinicalRecord): bool
    {
        return false;
    }

    private function allowFeature(User $user, string $feature): Response|bool
    {
        if (app(SubscriptionService::class)->canUseFeature($user, $feature)) {
            return true;
        }

        return Response::deny(__('A sua assinatura expirou ou não inclui esta funcionalidade. Consulte o seu plano em Configurações.'));
    }
}
