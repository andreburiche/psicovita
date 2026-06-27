<?php

namespace App\Policies;

use App\Models\SupportConversation;
use App\Models\User;

class SupportConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessSupportDesk();
    }

    public function view(User $user, SupportConversation $conversation): bool
    {
        if ($conversation->user_id === $user->id && config('psiconecta.chatbot.enabled', true)) {
            return true;
        }

        return $user->canAccessSupportDesk();
    }

    public function assign(User $user, SupportConversation $conversation): bool
    {
        return $user->canAccessSupportDesk()
            && $conversation->isOpen()
            && ($conversation->assigned_agent_id === null || $conversation->assigned_agent_id === $user->id);
    }

    public function message(User $user, SupportConversation $conversation): bool
    {
        return $user->canAccessSupportDesk()
            && $conversation->assigned_agent_id === $user->id
            && $conversation->status === \App\Enums\SupportConversationStatus::Assigned;
    }

    public function transfer(User $user, SupportConversation $conversation): bool
    {
        return $user->canAccessSupportDesk() && $conversation->isOpen();
    }

    public function resolve(User $user, SupportConversation $conversation): bool
    {
        return $user->canAccessSupportDesk()
            && $conversation->assigned_agent_id === $user->id
            && $conversation->isOpen();
    }

    public function close(User $user, SupportConversation $conversation): bool
    {
        return $user->canAccessSupportDesk() && $conversation->isOpen();
    }
}
