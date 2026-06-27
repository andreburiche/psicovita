<?php

namespace App\Notifications;

use App\Models\ProfessionalSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Support\BrandedMailMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class SubscriptionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ProfessionalSubscription $subscription,
        public Carbon $expiresAt,
        public int $daysRemaining,
        public bool $isTrial,
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
        $checkoutUrl = route('subscription.checkout', absolute: true);

        return BrandedMailMessage::create()
            ->subject($appName.' — '.__('A sua assinatura termina em breve'))
            ->view('emails.subscription-expiring', [
                'appName' => $appName,
                'userName' => $notifiable->name,
                'planName' => $this->subscription->plan?->name ?? __('Plano'),
                'expiresAt' => $this->expiresAt->format('d/m/Y'),
                'daysRemaining' => $this->daysRemaining,
                'isTrial' => $this->isTrial,
                'checkoutUrl' => $checkoutUrl,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'professional_subscription_id' => $this->subscription->id,
            'expires_on' => $this->expiresAt->toDateString(),
            'days_remaining' => $this->daysRemaining,
            'is_trial' => $this->isTrial,
            'title' => $this->isTrial
                ? __('Período de teste a terminar')
                : __('Assinatura a terminar'),
            'message' => $this->isTrial
                ? __('O seu teste termina em :days dia(s) (:date).', [
                    'days' => $this->daysRemaining,
                    'date' => $this->expiresAt->format('d/m/Y'),
                ])
                : __('A sua assinatura termina em :days dia(s) (:date).', [
                    'days' => $this->daysRemaining,
                    'date' => $this->expiresAt->format('d/m/Y'),
                ]),
            'action_url' => route('subscription.checkout'),
        ];
    }
}
