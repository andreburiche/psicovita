<?php

namespace App\Notifications;

use App\Models\PatientPortalInvitation;
use App\Models\User;
use App\Support\PatientPortalInvitationLinks;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PatientPortalInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PatientPortalInvitation $invitation,
        public User $professional,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = (string) config('app.name', 'PsiConecta');
        $activateUrl = PatientPortalInvitationLinks::activationUrl($this->invitation);

        return (new MailMessage)
            ->subject($appName.' — '.__('Acesso ao portal do paciente'))
            ->view('emails.patient-portal-invitation', [
                'appName' => $appName,
                'professionalName' => $this->professional->name,
                'patientName' => $this->invitation->patient?->name ?? $notifiable->name ?? '',
                'activateUrl' => $activateUrl,
                'expiresAt' => $this->invitation->expires_at->format('d/m/Y'),
            ]);
    }
}
