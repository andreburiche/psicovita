<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Notifications\Support\BrandedMailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WhatsAppConsentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Conversation $conversation,
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
        $url = route('conversations.show', $this->conversation, absolute: true);

        return BrandedMailMessage::create()
            ->subject($appName.' — '.__('Consentimento para sincronização WhatsApp'))
            ->greeting(__('Olá, :name', ['name' => $notifiable->name]))
            ->line(__(':professional solicitou sincronizar mensagens WhatsApp com a conversa privada na plataforma.', [
                'professional' => $this->professional->name,
            ]))
            ->line(__('Para activar, abra a conversa abaixo e toque em «Consentir sincronização WhatsApp». O registo do consentimento só pode ser feito dentro da aplicação, por segurança e conformidade LGPD.'))
            ->action(__('Abrir conversa e consentir'), $url)
            ->line(__('Se não reconhecer este pedido, ignore este e-mail ou contacte o seu terapeuta.'));
    }
}
