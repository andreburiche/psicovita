<?php

namespace App\Notifications;

use App\Models\ClinicInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Support\BrandedMailMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClinicTeamInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClinicInvitation $invitation,
        public User $owner,
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
        $acceptUrl = route('clinic.invitations.show', $this->invitation->token, absolute: true);

        return BrandedMailMessage::create()
            ->subject($appName.' — '.__('Convite para equipa clínica'))
            ->view('emails.clinic-team-invitation', [
                'appName' => $appName,
                'ownerName' => $this->owner->name,
                'acceptUrl' => $acceptUrl,
                'expiresAt' => $this->invitation->expires_at->format('d/m/Y'),
            ]);
    }
}
