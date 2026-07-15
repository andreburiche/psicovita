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
    /**
     * @var array{
     *   email_wanted: bool,
     *   whatsapp_wanted: bool,
     *   email_sent: bool,
     *   whatsapp_sent: bool,
     *   email_error: ?string,
     *   whatsapp_error: ?string
     * }|null
     */
    private ?array $lastDispatch = null;

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

        $this->lastDispatch = $this->dispatchInvitation(
            $invitation,
            $patient,
            $professional,
            $sendEmail,
            $sendWhatsApp,
        );

        AuditTrail::entity('portal_invite', 'patients', $patient, [
            'user_id' => $portalUser->id,
            'invitation_id' => $invitation->id,
            'send_email' => $sendEmail,
            'send_whatsapp' => $sendWhatsApp,
            'email_sent' => $this->lastDispatch['email_sent'],
            'whatsapp_sent' => $this->lastDispatch['whatsapp_sent'],
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
        $this->lastDispatch = $this->dispatchInvitation(
            $invitation,
            $patient,
            $professional,
            $sendEmail,
            $sendWhatsApp,
        );

        AuditTrail::entity('portal_invite_resend', 'patients', $patient, [
            'invitation_id' => $invitation->id,
            'send_email' => $sendEmail,
            'send_whatsapp' => $sendWhatsApp,
            'email_sent' => $this->lastDispatch['email_sent'],
            'whatsapp_sent' => $this->lastDispatch['whatsapp_sent'],
        ], $professional);

        return $invitation;
    }

    public function inviteSentMessage(bool $sendEmail = true, bool $sendWhatsApp = false, ?Patient $patient = null): string
    {
        $dispatch = $this->lastDispatch ?? [
            'email_wanted' => $sendEmail,
            'whatsapp_wanted' => $sendWhatsApp,
            'email_sent' => false,
            'whatsapp_sent' => false,
            'email_error' => null,
            'whatsapp_error' => $sendWhatsApp
                ? ($patient && ! $this->whatsapp->patientHasPhone($patient)
                    ? __('telefone em falta na ficha')
                    : __('não enviado'))
                : null,
        ];

        $parts = [];

        if ($dispatch['email_sent']) {
            $parts[] = __('e-mail');
        } elseif ($dispatch['email_wanted']) {
            $parts[] = __('e-mail falhou (:reason)', [
                'reason' => $dispatch['email_error'] ?: __('erro desconhecido'),
            ]);
        }

        if ($dispatch['whatsapp_sent']) {
            $parts[] = __('WhatsApp');
        } elseif ($dispatch['whatsapp_wanted']) {
            $parts[] = __('WhatsApp falhou (:reason)', [
                'reason' => $dispatch['whatsapp_error'] ?: __('erro desconhecido'),
            ]);
        }

        if ($dispatch['email_sent'] && $dispatch['whatsapp_sent']) {
            return __('Convite do portal enviado por e-mail e WhatsApp.');
        }

        if ($dispatch['email_sent'] && ! $dispatch['whatsapp_wanted']) {
            return __('Convite do portal enviado por e-mail.');
        }

        if ($dispatch['whatsapp_sent'] && ! $dispatch['email_wanted']) {
            return __('Convite do portal enviado por WhatsApp.');
        }

        if ($dispatch['email_sent'] && $dispatch['whatsapp_wanted'] && ! $dispatch['whatsapp_sent']) {
            return __('Convite enviado por e-mail. WhatsApp não enviado — :reason.', [
                'reason' => $dispatch['whatsapp_error'] ?: __('falha na integração'),
            ]);
        }

        if ($dispatch['whatsapp_sent'] && $dispatch['email_wanted'] && ! $dispatch['email_sent']) {
            return __('Convite enviado por WhatsApp. E-mail não enviado — :reason.', [
                'reason' => $dispatch['email_error'] ?: __('falha no envio'),
            ]);
        }

        if ($parts === []) {
            return __('Conta do portal criada. Nenhum convite foi enviado.');
        }

        return __('Conta do portal criada. :detail', [
            'detail' => implode('; ', $parts).'.',
        ]);
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
        $expiresAt = now()->addDays(max(1, (int) config('patient_portal.invitation_expires_days', 7)));
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

    /**
     * @return array{
     *   email_wanted: bool,
     *   whatsapp_wanted: bool,
     *   email_sent: bool,
     *   whatsapp_sent: bool,
     *   email_error: ?string,
     *   whatsapp_error: ?string
     * }
     */
    private function dispatchInvitation(
        PatientPortalInvitation $invitation,
        Patient $patient,
        User $professional,
        bool $sendEmail,
        bool $sendWhatsApp,
    ): array {
        $result = [
            'email_wanted' => $sendEmail,
            'whatsapp_wanted' => $sendWhatsApp,
            'email_sent' => false,
            'whatsapp_sent' => false,
            'email_error' => null,
            'whatsapp_error' => null,
        ];

        if ($sendEmail) {
            try {
                $this->sendInvitationEmail($invitation, $professional);
                $result['email_sent'] = true;
            } catch (\Throwable $e) {
                report($e);
                $result['email_error'] = $e->getMessage();
            }
        }

        if ($sendWhatsApp) {
            if (! $this->whatsapp->isAvailable()) {
                $result['whatsapp_error'] = __('integração WhatsApp não configurada ou desligada');
            } elseif (! $this->whatsapp->patientHasPhone($patient)) {
                $result['whatsapp_error'] = __('adicione o telefone na ficha');
            } else {
                $messageId = $this->whatsapp->sendPortalInvitation($patient, $invitation, $professional);
                if (filled($messageId)) {
                    $result['whatsapp_sent'] = true;
                } else {
                    $result['whatsapp_error'] = __('servidor WhatsApp inacessível ou rejeitou o envio (verifique a Evolution/Meta)');
                }
            }
        }

        return $result;
    }

    private function sendInvitationEmail(PatientPortalInvitation $invitation, User $professional): void
    {
        $invitation->loadMissing(['patient', 'user']);

        $email = $invitation->user->email;
        if (! filled($email)) {
            throw new \RuntimeException(__('A conta do portal não tem e-mail.'));
        }

        Notification::route('mail', $email)
            ->notifyNow(new PatientPortalInvitationNotification($invitation, $professional));

        // Regrava expires_at para não depender de ON UPDATE CURRENT_TIMESTAMP em MySQL antigo.
        $invitation->update([
            'last_sent_at' => now(),
            'expires_at' => $invitation->expires_at,
        ]);
    }

    private function normalizedEmail(Patient $patient): string
    {
        $email = $patient->email;

        return is_string($email) ? Str::lower(trim($email)) : '';
    }
}
