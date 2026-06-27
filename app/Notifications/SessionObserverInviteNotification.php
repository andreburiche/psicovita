<?php

namespace App\Notifications;

use App\Models\SessionParticipant;
use Illuminate\Bus\Queueable;
use App\Notifications\Support\BrandedMailMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionObserverInviteNotification extends Notification
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
        $this->participant->loadMissing('therapySession.professional', 'therapySession.patient');
        $session = $this->participant->therapySession;

        $professionalName = $session->professional?->name ?: config('app.name');
        $joinUrl = $this->participant->joinUrl();

        $mail = BrandedMailMessage::create()
            ->subject(__('Convite para escuta de sessão — :app', ['app' => config('app.name')]))
            ->greeting(__('Olá, :name', ['name' => $this->participant->display_name]));

        if ($session->patient) {
            $mail->line(__('Foi convidado(a) como observador(a) numa sessão terapêutica com :patient, conduzida por :professional.', [
                'patient' => $session->patient->name,
                'professional' => $professionalName,
            ]));
        } else {
            $mail->line(__('Foi convidado(a) como observador(a) numa sessão de escuta / supervisão conduzida por :professional.', [
                'professional' => $professionalName,
            ]));
        }

        return $mail
            ->line(__('Entrará na sala em modo silencioso (sem áudio nem vídeo) para acompanhar a sessão.'))
            ->action(__('Entrar na sala'), $joinUrl)
            ->line(__('Este link é pessoal e intransferível. Guarde-o em local seguro.'));
    }
}
