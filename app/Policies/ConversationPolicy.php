<?php

namespace App\Policies;

use App\Models\ClinicalRecord;
use App\Models\Conversation;
use App\Models\Patient;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProfessional() || $user->isPatient();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $conversation->involvesUser($user);
    }

    public function create(User $user): bool
    {
        return $user->isProfessional() || $user->isPatient();
    }

    public function message(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }

    public function startWithPatient(User $user, Patient $patient): bool
    {
        return $user->isProfessional()
            && $patient->professional_id === $user->clinicalPracticeId();
    }

    public function export(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation) && $user->isProfessional();
    }

    public function archiveToRecord(User $user, Conversation $conversation): bool
    {
        return $user->isProfessional()
            && $this->view($user, $conversation)
            && $conversation->patient_id !== null
            && $user->can('create', ClinicalRecord::class);
    }
}
