<?php

namespace App\Services;

use App\Models\ClinicInvitation;
use App\Models\User;
use App\Notifications\ClinicTeamInvitationNotification;
use App\Support\ContactHasher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ClinicTeamService
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function canManageTeam(User $user): bool
    {
        if (! $user->isProfessional() || $user->isClinicTeamMember()) {
            return false;
        }

        return $this->subscriptions->canUseFeature($user, 'multi_user');
    }

    /**
     * @return Collection<int, User>
     */
    public function teamMembers(User $owner): Collection
    {
        return User::query()
            ->where('clinic_owner_id', $owner->id)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, ClinicInvitation>
     */
    public function pendingInvitations(User $owner): Collection
    {
        return ClinicInvitation::query()
            ->where('clinic_owner_id', $owner->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();
    }

    public function invite(User $owner, string $email): ClinicInvitation
    {
        if (! $this->canManageTeam($owner)) {
            throw new \InvalidArgumentException(__('O seu plano não inclui multi-utilizador ou não pode gerir a equipa.'));
        }

        $normalized = Str::lower(trim($email));
        if ($normalized === '') {
            throw new \InvalidArgumentException(__('Informe um e-mail válido.'));
        }

        if ($owner->normalizedEmail() === $normalized) {
            throw new \InvalidArgumentException(__('Não pode convidar o seu próprio e-mail.'));
        }

        $existingMember = User::query()
            ->where('email_hash', ContactHasher::emailHash($normalized))
            ->where('clinic_owner_id', $owner->id)
            ->exists();

        if ($existingMember) {
            throw new \InvalidArgumentException(__('Este utilizador já faz parte da equipa.'));
        }

        $memberCount = $this->teamMembers($owner)->count() + $this->pendingInvitations($owner)->count();
        if ($memberCount >= (int) config('clinic.max_team_members', 5)) {
            throw new \InvalidArgumentException(__('Limite de membros da equipa atingido.'));
        }

        $invitation = ClinicInvitation::query()->updateOrCreate(
            [
                'clinic_owner_id' => $owner->id,
                'email_hash' => ContactHasher::emailHash($normalized),
                'accepted_at' => null,
            ],
            [
                'email' => $normalized,
                'token' => Str::lower(Str::random(48)),
                'expires_at' => now()->addDays((int) config('clinic.invitation_expires_days', 7)),
            ],
        );

        Notification::route('mail', $normalized)
            ->notify(new ClinicTeamInvitationNotification($invitation, $owner));

        return $invitation;
    }

    public function findValidInvitation(string $token): ?ClinicInvitation
    {
        $invitation = ClinicInvitation::query()
            ->where('token', $token)
            ->with('owner')
            ->first();

        if ($invitation === null || ! $invitation->isPending()) {
            return null;
        }

        return $invitation;
    }

    public function acceptInvitation(ClinicInvitation $invitation, User $user): User
    {
        if (! $invitation->isPending()) {
            throw new \InvalidArgumentException(__('Convite inválido ou expirado.'));
        }

        if ($user->normalizedEmail() !== Str::lower(trim($invitation->email))) {
            throw new \InvalidArgumentException(__('Este convite foi enviado para outro e-mail.'));
        }

        if (! $user->isProfessional()) {
            throw new \InvalidArgumentException(__('Apenas contas profissionais podem aceitar convites de equipa.'));
        }

        if ($user->isClinicTeamMember()) {
            throw new \InvalidArgumentException(__('Já pertence a outra equipa clínica.'));
        }

        if ($user->patients()->exists()) {
            throw new \InvalidArgumentException(__('Contas com consultório próprio não podem entrar numa equipa. Use outra conta.'));
        }

        if ($this->teamMembers($invitation->owner)->count() >= (int) config('clinic.max_team_members', 5)) {
            throw new \InvalidArgumentException(__('A equipa já atingiu o limite de membros.'));
        }

        $user->update(['clinic_owner_id' => $invitation->clinic_owner_id]);

        $invitation->update([
            'accepted_at' => now(),
            'accepted_user_id' => $user->id,
        ]);

        return $user->fresh();
    }

    public function removeMember(User $owner, User $member): void
    {
        if (! $this->canManageTeam($owner)) {
            throw new \InvalidArgumentException(__('Sem permissão para gerir a equipa.'));
        }

        if ((int) $member->clinic_owner_id !== (int) $owner->id) {
            throw new \InvalidArgumentException(__('Este utilizador não pertence à sua equipa.'));
        }

        $member->update(['clinic_owner_id' => null]);
    }

    public function membershipIsActive(User $member): bool
    {
        if (! $member->isClinicTeamMember()) {
            return true;
        }

        $owner = $member->clinicOwner;

        return $owner !== null && $this->canManageTeam($owner);
    }

    /**
     * Desliga membros e convites pendentes quando o titular já não tem plano Clínica activo.
     */
    public function releaseTeamIfUnavailable(User $owner): int
    {
        if ($this->canManageTeam($owner)) {
            return 0;
        }

        $released = 0;

        foreach ($this->teamMembers($owner) as $member) {
            $member->update(['clinic_owner_id' => null]);
            $released++;
        }

        ClinicInvitation::query()
            ->where('clinic_owner_id', $owner->id)
            ->whereNull('accepted_at')
            ->delete();

        return $released;
    }

    /**
     * Sincroniza equipas de todos os titulares com membros (ex.: após expiração de assinatura).
     */
    public function releaseUnavailableTeams(): int
    {
        $released = 0;

        User::query()
            ->whereNull('clinic_owner_id')
            ->whereHas('clinicTeamMembers')
            ->chunkById(50, function ($owners) use (&$released): void {
                foreach ($owners as $owner) {
                    $released += $this->releaseTeamIfUnavailable($owner);
                }
            });

        return $released;
    }
}
