<?php

namespace App\Notifications;

use App\Notifications\Support\BrandedMailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ProfessionalDailyAgendaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<array{time: string, label: string, mode: ?string, status: string}>  $sessions
     */
    public function __construct(
        public Carbon $agendaDate,
        public array $sessions,
        public string $summaryMessage,
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
        $agendaUrl = route('schedule.index', absolute: true);

        $mail = BrandedMailMessage::create()
            ->subject($appName.' — '.__('Agenda de hoje'))
            ->greeting(__('Bom dia, :name!', ['name' => $notifiable->name]))
            ->line($this->summaryMessage);

        if ($this->sessions !== []) {
            foreach ($this->sessions as $item) {
                $mail->line('• '.$item['time'].' — '.$item['label'].($item['mode'] ? ' ('.$item['mode'].')' : ''));
            }
        }

        return $mail->action(__('Ver agenda completa'), $agendaUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'daily_agenda',
            'agenda_date' => $this->agendaDate->toDateString(),
            'sessions_count' => count($this->sessions),
            'sessions' => $this->sessions,
            'title' => __('Agenda de hoje'),
            'message' => $this->summaryMessage,
            'action_url' => route('schedule.index'),
        ];
    }
}
