<?php

namespace App\Notifications;

use App\Models\TherapySession;
use App\Notifications\Support\BrandedMailMessage;
use App\Support\TherapySessionSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfessionalUpcomingSessionReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TherapySession $session,
        public int $minutesBefore,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = (string) config('app.name', 'PsiConecta');
        $time = TherapySessionSchedule::formatTime($this->session);
        $patient = $this->session->displayLabel();

        return BrandedMailMessage::create()
            ->subject($appName.' — '.__('Próxima sessão em :minutes min', ['minutes' => $this->minutesBefore]))
            ->greeting(__('Olá, :name!', ['name' => $notifiable->name]))
            ->line(__('Em :minutes minutos você tem sessão com :patient às :time.', [
                'minutes' => $this->minutesBefore,
                'patient' => $patient,
                'time' => $time,
            ]))
            ->action(__('Abrir sessão'), route('therapy-sessions.show', $this->session, absolute: true));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $time = TherapySessionSchedule::formatTime($this->session);
        $patient = $this->session->displayLabel();

        return [
            'kind' => 'upcoming_session',
            'therapy_session_id' => $this->session->id,
            'patient_name' => $this->session->patient?->name,
            'session_date' => $this->session->session_date?->toDateString(),
            'session_time' => $this->session->session_time,
            'minutes_before' => $this->minutesBefore,
            'title' => __('Próxima sessão em :minutes min', ['minutes' => $this->minutesBefore]),
            'message' => __('Sessão com :patient às :time.', [
                'patient' => $patient,
                'time' => $time,
            ]),
            'action_url' => route('therapy-sessions.show', $this->session),
        ];
    }
}
