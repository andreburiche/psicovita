<?php

namespace App\Policies;

use App\Models\ScheduleBlock;
use App\Models\User;

class ScheduleBlockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProfessional();
    }

    public function view(User $user, ScheduleBlock $scheduleBlock): bool
    {
        return $scheduleBlock->professional_id === $user->clinicalPracticeId();
    }

    public function create(User $user): bool
    {
        return $user->isProfessional();
    }

    public function update(User $user, ScheduleBlock $scheduleBlock): bool
    {
        return $scheduleBlock->professional_id === $user->clinicalPracticeId();
    }

    public function delete(User $user, ScheduleBlock $scheduleBlock): bool
    {
        return $scheduleBlock->professional_id === $user->clinicalPracticeId();
    }

    public function restore(User $user, ScheduleBlock $scheduleBlock): bool
    {
        return false;
    }

    public function forceDelete(User $user, ScheduleBlock $scheduleBlock): bool
    {
        return false;
    }
}
