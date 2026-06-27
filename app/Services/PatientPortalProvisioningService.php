<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\PatientPortalInvitation;
use App\Models\User;
use App\Notifications\PatientPortalInvitationNotification;
use App\Support\AuditTrail;
use App\Support\ContactHasher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PatientPortalProvisioningService
{
    public function __construct(
        private readonly WhatsAppTransactionalService $whatsapp,
    ) {}

    /**
     * @return array{
     *   status: string,
     *   label: string,
     *   portal_user: User|null,
     *   pending_invitation: PatientPortalInvitation|null,
     *   can_provision: bool,
     *   can_resend: bool,
     *   whatsapp_available: bool,
     *   whatsapp_has_phone: bool
     * }
     */
    public function statusContext(Patient $patient): array
    {
        $base = $this->buildStatusContext($patient);

        return array_merge($base, [
            'whatsapp_available' => $this->whatsapp->isAvailable(),
            'whatsapp_has_phone' => $this->whatsapp->patientHasPhone($patient),
        ]);
    }

    /**
     * @return array{
     *   status: string,
     *   label: string,
     *   portal_user: User|null,
     *   pending_invitation: PatientPortalInvitation|null,
     *   can_provision: bool,
     *   can_resend: bool
     * }
     */
    private function buildStatusContext(Patient $patient): array
    {
        $patient->loadMissing('portalInvitations');
        $portalUser = $patient->portalUser();

        if (! filled($patient->email)) {
            return [
                'status' => 'no_email',
                'label' => __('Sem e-mail na ficha'),
                'portal_user' => null,
                'pending_invitation' => null,
                'can_provision' => false,
                'can_resend' => false,
            ];
        }

        if ($portalUser !== null && $portalUser->email_verified_at !== null) {
            return [
                'status' => 'active',
                'label' => __('Portal activo'),
                'portal_user' => $portalUser,
                'pending_invitation' => null,
                'can_provision' => false,
                'can_resend' => false,
            ];
        }

        $pendingInvitation = $patient->portalInvitations
            ->first(fn (PatientPortalInvitation $invitation) => $invitation->isPending());

        if ($portalUser !== null) {
            return [
                'status' => $pendingInvitation ? 'pending' : 'inactive',
                'label' => $pendingInvitation
                    ? __('Convite pendente')
                    : __('Conta criada — convite expirado'),
                'portal_user' => $portalUser,
                'pending_invitation' => $pendingInvitation,
                'can_provision' => false,
                'can_resend' => true,
            ];
        }

        return [
            'status' => 'none',
            'label' => __('Sem conta no portal'),
            'portal_user' => null,
            'pending_invitation' => null,
            'can_provision' => true,
            'can_resend' => false,
        ];
    }

    public function provision(
        Patient $patient,
        User $professional,
        bool $sendEmail = true,
        bool $sendWhatsApp = false,
    ): PatientPortalInvitation {
        $email = $this->normalizedEmail($patient);
        if ($email === '') {
            throw new \InvalidArgumentException(__('Informe o e-mail do paciente para criar acesso ao portal.'));
        }

        $context = $this->buildStatusContext($patient);
        if ($context['status'] === 'active') {
            throw new \InvalidArgumentException(__('Este paciente já tem acesso activo ao portal.'));
        }

        $portalUser = $context['portal_user'] ?? $this->resolveOrCreatePortalUser($patient, $professional, $email);

        $invitation = $this->createOrRefreshInvitation($patient, $portalUser, $professional);

        $this->dispatchInvitation($invitation, $patient, $professional, $sendEmail, $sendWhatsApp);

        AuditTrail::entity('portal_invite', 'patients', $patient, [
            'user_id' => $portalUser->id,
            'invitation_id' => $invitation->id,
            'send_email' => $sendEmail,
            'send_whatsapp' => $sendWhatsApp,
        ], $professional);

        return $invitation;
    }

    public function resend(
        Patient $patient,
        User $professional,
        bool $sendEmail = true,
        bool $sendWhatsApp = true,
    ): PatientPortalInvitation {
        $context = $this->buildStatusContext($patient);

        if ($context['status'] === 'no_email') {
            throw new \InvalidArgumentException(__('O paciente precisa de e-mail na ficha para reenviar o convite.'));
        }

        if ($context['status'] === 'active') {
            throw new \InvalidArgumentException(__('O portal já está activo para este paciente.'));
        }

        if ($context['status'] === 'none') {
            return $this->provision($patient, $professional, $sendEmail, $sendWhatsApp);
        }

        $portalUser = $context['portal_user'] ?? $this->resolveOrCreatePortalUser(
            $patient,
            $professional,
            $this->normalizedEmail($patient),
        );

        $invitation = $this->createOrRefreshInvitation($patient, $portalUser, $professional);
        $this->dispatchInvitation($invitation, $patient, $professional, $sendEmail, $sendWhatsApp);

        AuditTrail::entity('portal_invite_resend', 'patients', $patient, [
            'invitation_id' => $invitation->id,
            'send_email' => $sendEmail,
            'send_whatsapp' => $sendWhatsApp,
        ], $professional);

        return $invitation;
    }

    public function inviteSentMessage(bool $sendEmail, bool $sendWhatsApp, Patient $patient): string
    {
        $whatsappSent = $sendWhatsApp && $this->whatsapp->isAvailable() && $this->whatsapp->patientHasPhone($patient);
        $emailSent = $sendEmail;

        if ($emailSent && $whatsappSent) {
            return __('Convite do portal enviado por e-mail e WhatsApp.');
        }

        if ($emailSent) {
            if ($sendWhatsApp && ! $this->whatsapp->patientHasPhone($patient)) {
                return __('Convite enviado por e-mail. WhatsApp não enviado — adicione o telefone na ficha.');
            }

            if ($sendWhatsApp && ! $this->whatsapp->isAvailable()) {
                return __('Convite enviado por e-mail. WhatsApp indisponível (integração não configurada).');
            }

            return __('Convite do portal enviado por e-mail.');
        }

        if ($whatsappSent) {
            return __('Convite do portal enviado por WhatsApp.');
        }

        return __('Conta do portal criada. Nenhum convite foi enviado.');
    }

    public function findValidInvitation(string $token): ?PatientPortalInvitation
    {
        $invitation = PatientPortalInvitation::query()
            ->with(['patient', 'user', 'invitedBy'])
            ->where('token', $token)
            ->first();

        if ($invitation === null || ! $invitation->isPending()) {
            return null;
        }

        return $invitation;
    }

    /**
     * @throws ValidationException
     */
    public function activate(PatientPortalInvitation $invitation, string $password): User
    {
        $user = $invitation->user;

        $user->forceFill([
            'password' => Hash::make($password),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $invitation->update(['accepted_at' => now()]);

        AuditTrail::entity('portal_activate', 'users', $user, [
            'patient_id' => $invitation->patient_id,
            'invitation_id' => $invitation->id,
        ], $user);

        return $user->fresh();
    }

    private function resolveOrCreatePortalUser(Patient $patient, User $professional, string $email): User
    {
        $emailHash = ContactHasher::emailHash($email);
        $existing = User::query()->where('email_hash', $emailHash)->first();

        if ($existing !== null) {
            if ($existing->isProfessional() || $existing->isAdmin()) {
                throw new \InvalidArgumentException(__('Este e-mail já pertence a uma conta profissional. Use outro e-mail na ficha do paciente.'));
            }

            if (! $existing->isPatient()) {
                throw new \InvalidArgumentException(__('Este e-mail já está em uso por outro tipo de conta.'));
            }

            if ($existing->professional_id === null) {
                $existing->update(['professional_id' => $patient->professional_id]);
            }

            return $existing;
        }

        return User::query()->create([
            'name' => $patient->name,
            'email' => $email,
            'password' => Hash::make(Str::random(48)),
            'role' => UserRole::Patient,
            'professional_id' => $patient->professional_id,
            'email_verified_at' => null,
        ]);
    }

    private function createOrRefreshInvitation(Patient $patient, User $portalUser, User $professional): PatientPortalInvitation
    {
        $expiresAt = now()->addDays((int) config('patient_portal.invitation_expires_days', 7));
        $token = Str::lower(Str::random(48));

        $invitation = PatientPortalInvitation::query()
            ->where('patient_id', $patient->id)
            ->whereNull('accepted_at')
            ->latest('id')
            ->first();

        if ($invitation === null) {
            return PatientPortalInvitation::query()->create([
                'patient_id' => $patient->id,
                'user_id' => $portalUser->id,
                'invited_by' => $professional->id,
                'token' => $token,
                'expires_at' => $expiresAt,
            ]);
        }

        $invitation->update([
            'user_id' => $portalUser->id,
            'invited_by' => $professional->id,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        return $invitation->fresh();
    }

    private function sendInvitationEmail(PatientPortalInvitation $invitation, User $professional): void
    {
        $invitation->loadMissing(['patient', 'user']);

        $email = $invitation->user->email;
        if (! filled($email)) {
            return;
        }

        Notification::route('mail', $email)
            ->notify(new PatientPortalInvitationNotification($invitation, $professional));

        $invitation->update(['last_sent_at' => now()]);
    }

    private function dispatchInvitation(
        PatientPortalInvitation $invitation,
        Patient $patient,
        User $professional,
        bool $sendEmail,
        bool $sendWhatsApp,
    ): void {
        if ($sendEmail) {
            $this->sendInvitationEmail($invitation, $professional);
        }

        if ($sendWhatsApp) {
            $this->whatsapp->sendPortalInvitation($patient, $invitation, $professional);
        }
    }

    private function normalizedEmail(Patient $patient): string
    {
        $email = $patient->email;

        return is_string($email) ? Str::lower(trim($email)) : '';
    }
}
