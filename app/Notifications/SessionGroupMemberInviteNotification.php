<?php

namespace App\Notifications;

use App\Models\SessionParticipant;
use Illuminate\Bus\Queueable;
use App\Notifications\Support\BrandedMailMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionGroupMemberInviteNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SessionParticipant $participant,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->participant->loadMissing('therapySession.professional');
        $session = $this->participant->therapySession;

        $professionalName = $session->professional?->name ?: config('app.name');
        $joinUrl = $this->participant->joinUrl();

        return BrandedMailMessage::create()
            ->subject(__('Convite para sessão de grupo — :app', ['app' => config('app.name')]))
            ->greeting(__('Olá, :name', ['name' => $this->participant->display_name]))
            ->line(__('Você foi convidado(a) para uma sessão de grupo terapêutico conduzida por :professional.', [
                'professional' => $professionalName,
            ]))
            ->line(__('Utilize o botão abaixo para entrar na sala de vídeo no horário agendado.'))
            ->action(__('Entrar na sala'), $joinUrl)
            ->line(__('Este link é pessoal e intransferível. Guarde-o em local seguro.'));
    }
}
