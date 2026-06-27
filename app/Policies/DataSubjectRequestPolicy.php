<?php

namespace App\Policies;

use App\Models\DataSubjectRequest;
use App\Models\User;

class DataSubjectRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageLgpdRequests();
    }

    public function view(User $user, DataSubjectRequest $dataSubjectRequest): bool
    {
        return $user->canManageLgpdRequests();
    }

    public function update(User $user, DataSubjectRequest $dataSubjectRequest): bool
    {
        return $user->canManageLgpdRequests();
    }
}
