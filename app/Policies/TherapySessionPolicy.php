<?php

namespace App\Policies;

use App\Models\TherapySession;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\Response;

class TherapySessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProfessional();
    }

    public function view(User $user, TherapySession $therapySession): bool
    {
        return $therapySession->professional_id === $user->clinicalPracticeId();
    }

    public function create(User $user): Response|bool
    {
        if (! $user->isProfessional()) {
            return false;
        }

        return $this->allowFeature($user, 'create_session');
    }

    public function update(User $user, TherapySession $therapySession): bool
    {
        return $therapySession->professional_id === $user->clinicalPracticeId();
    }

    public function delete(User $user, TherapySession $therapySession): bool
    {
        return $therapySession->professional_id === $user->clinicalPracticeId();
    }

    public function restore(User $user, TherapySession $therapySession): bool
    {
        return false;
    }

    public function forceDelete(User $user, TherapySession $therapySession): bool
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
