<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use App\Notifications\Support\BrandedMailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewConversationMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Conversation $conversation,
        public Message $message,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->isPatient() || $notifiable->isProfessional()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = (string) config('app.name', 'PsiConecta');
        $senderName = $this->message->sender?->name ?? __('Alguém');
        $preview = Str::limit(strip_tags((string) $this->message->body), 120);
        $url = route('conversations.show', $this->conversation, absolute: true);

        return BrandedMailMessage::create()
            ->subject($appName.' — '.__('Nova mensagem de :name', ['name' => $senderName]))
            ->greeting(__('Olá, :name', ['name' => $notifiable->name]))
            ->line(__('Recebeu uma nova mensagem na conversa terapêutica.'))
            ->line('> «'.$preview.'»')
            ->action(__('Abrir conversa'), $url)
            ->line(__('Por privacidade, o conteúdo completo só está disponível dentro da plataforma.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'message_id' => $this->message->id,
            'sender_name' => $this->message->sender?->name,
            'preview' => Str::limit((string) $this->message->body, 120),
            'channel' => $this->message->channel->value,
            'title' => __('Nova mensagem de :name', [
                'name' => $this->message->sender?->name ?? __('Alguém'),
            ]),
            'message' => Str::limit(strip_tags((string) $this->message->body), 120),
            'action_url' => route('conversations.show', $this->conversation),
        ];
    }
}
