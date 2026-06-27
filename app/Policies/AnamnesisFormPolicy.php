<?php

namespace App\Policies;

use App\Models\AnamnesisForm;
use App\Models\User;

class AnamnesisFormPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProfessional();
    }

    public function view(User $user, AnamnesisForm $anamnesisForm): bool
    {
        return $user->isProfessional() && (int) $anamnesisForm->professional_id === $user->clinicalPracticeId();
    }

    public function create(User $user): bool
    {
        return $user->isProfessional();
    }

    public function update(User $user, AnamnesisForm $anamnesisForm): bool
    {
        return $this->view($user, $anamnesisForm);
    }

    public function delete(User $user, AnamnesisForm $anamnesisForm): bool
    {
        return $this->view($user, $anamnesisForm);
    }
}
