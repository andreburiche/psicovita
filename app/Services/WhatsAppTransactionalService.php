<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Patient;
use App\Models\PatientPortalInvitation;
use App\Models\User;
use App\Support\PatientPortalInvitationLinks;
use App\Services\WhatsApp\WhatsAppGatewayFactory;
use App\Services\WhatsApp\WhatsAppIncomingHandler;

class WhatsAppTransactionalService
{
    public function __construct(
        private readonly WhatsAppGatewayFactory $factory,
    ) {}

    public function isAvailable(): bool
    {
        if (! config('psiconecta.whatsapp.enabled')) {
            return false;
        }

        return $this->factory->make()->isConfigured();
    }

    public function patientHasPhone(Patient $patient): bool
    {
        return $this->resolvePatientPhone($patient) !== null;
    }

    public function conversationPatientHasPhone(Conversation $conversation): bool
    {
        $phone = $this->resolveConversationPhone($conversation);

        return $phone !== null;
    }

    public function sendPortalInvitation(
        Patient $patient,
        PatientPortalInvitation $invitation,
        User $professional,
    ): ?string {
        if (! $this->isAvailable()) {
            return null;
        }

        $phone = $this->resolvePatientPhone($patient);
        if ($phone === null) {
            return null;
        }

        $invitation->loadMissing('patient');
        $activateUrl = PatientPortalInvitationLinks::activationUrl($invitation);
        $appName = (string) config('app.name', 'PsiConecta');

        $body = PatientPortalInvitationLinks::whatsAppBody(
            $professional->name,
            $appName,
            $activateUrl,
            $invitation->expires_at->format('d/m/Y'),
        );

        return $this->factory->make()->sendText($phone, $body);
    }

    public function sendProfessionalText(User $professional, string $body): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        if (! $professional->whatsapp_notifications) {
            return null;
        }

        $phone = $this->resolveProfessionalPhone($professional);
        if ($phone === null) {
            return null;
        }

        return $this->factory->make()->sendText($phone, $body);
    }

    public function sendConsentReminder(Conversation $conversation, User $professional): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $phone = $this->resolveConversationPhone($conversation);
        if ($phone === null) {
            return null;
        }

        $conversation->loadMissing('patientUser');
        $patientName = $conversation->patientUser?->name ?? __('paciente');
        $appName = (string) config('app.name', 'PsiConecta');
        $url = route('conversations.show', $conversation, absolute: true);

        $body = implode("\n", [
            __('Olá :name,', ['name' => $patientName]),
            '',
            __(':professional solicitou sincronizar o WhatsApp com a conversa privada no :app.', [
                'professional' => $professional->name,
                'app' => $appName,
            ]),
            '',
            __('Para activar, abra o link, entre na sua conta e toque em «Consentir sincronização WhatsApp» (só na aplicação):'),
            '',
            $url,
        ]);

        return $this->factory->make()->sendText($phone, $body);
    }

    private function resolveConversationPhone(Conversation $conversation): ?string
    {
        $conversation->loadMissing(['patient', 'patientUser']);
        $phone = $conversation->patient?->phone ?: $conversation->patientUser?->phone;
        if (! filled($phone)) {
            return null;
        }

        $normalized = WhatsAppIncomingHandler::normalizePhone((string) $phone);

        return $normalized !== '' ? $normalized : null;
    }

    private function resolveProfessionalPhone(User $professional): ?string
    {
        if (! filled($professional->phone)) {
            return null;
        }

        $normalized = WhatsAppIncomingHandler::normalizePhone((string) $professional->phone);

        return $normalized !== '' ? $normalized : null;
    }

    private function resolvePatientPhone(Patient $patient): ?string
    {
        $phone = $patient->phone ?: $patient->portalUser()?->phone;
        if (! filled($phone)) {
            return null;
        }

        $normalized = WhatsAppIncomingHandler::normalizePhone((string) $phone);

        return $normalized !== '' ? $normalized : null;
    }
}
