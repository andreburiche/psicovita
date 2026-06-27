<?php

namespace App\Notifications;

use App\Models\TherapySession;
use Illuminate\Notifications\Notification;

class TherapySessionTomorrowReminder extends Notification
{
    public function __construct(
        public TherapySession $session
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'therapy_session_id' => $this->session->id,
            'patient_name' => $this->session->patient?->name,
            'session_date' => $this->session->session_date?->toDateString(),
            'session_time' => $this->session->session_time,
            'title' => __('Sessão amanhã'),
            'message' => __('Lembrete: sessão com :patient em :date às :time.', [
                'patient' => $this->session->patient?->name ?? __('paciente'),
                'date' => $this->session->session_date?->translatedFormat('d/m/Y') ?? '',
                'time' => $this->formattedTime(),
            ]),
        ];
    }

    private function formattedTime(): string
    {
        $raw = $this->session->session_time;
        if ($raw === null || $raw === '') {
            return '';
        }

        if (is_string($raw) && strlen($raw) >= 5) {
            return substr($raw, 0, 5);
        }

        return (string) $raw;
    }
}
