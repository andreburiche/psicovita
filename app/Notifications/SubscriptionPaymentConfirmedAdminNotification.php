<?php

namespace App\Notifications;

use App\Models\ProfessionalSubscription;
use App\Notifications\Support\BrandedMailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentConfirmedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ProfessionalSubscription $subscription,
        public bool $isRenewal,
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
        $subscription = $this->subscription->loadMissing(['user', 'plan']);
        $appName = (string) config('app.name', 'PsiConecta');
        $panelUrl = route('admin.subscriptions.index', absolute: true);

        $subject = $this->isRenewal
            ? __('Renovação de assinatura confirmada')
            : __('Nova assinatura paga');

        return BrandedMailMessage::create()
            ->subject($appName.' — '.$subject)
            ->greeting(__('Olá, :name!', ['name' => $notifiable->name]))
            ->line($this->summaryLine($subscription))
            ->line(__('Plano: :plan', ['plan' => $subscription->plan?->name ?? '—']))
            ->line(__('Validade até: :date', [
                'date' => $subscription->ends_at?->format('d/m/Y') ?? '—',
            ]))
            ->action(__('Ver painel de assinaturas'), $panelUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $subscription = $this->subscription->loadMissing(['user', 'plan']);

        return [
            'kind' => $this->isRenewal ? 'subscription_renewal' : 'subscription_payment',
            'professional_subscription_id' => $subscription->id,
            'professional_id' => $subscription->user_id,
            'professional_name' => $subscription->user?->name,
            'plan_name' => $subscription->plan?->name,
            'is_renewal' => $this->isRenewal,
            'title' => $this->isRenewal
                ? __('Renovação confirmada')
                : __('Assinatura paga'),
            'message' => $this->summaryLine($subscription),
            'action_url' => route('admin.subscriptions.index'),
        ];
    }

    private function summaryLine(ProfessionalSubscription $subscription): string
    {
        $name = $subscription->user?->name ?? __('Profissional');

        return $this->isRenewal
            ? __(':name renovou o plano :plan.', [
                'name' => $name,
                'plan' => $subscription->plan?->name ?? __('PsiConecta'),
            ])
            : __(':name concluiu o pagamento do plano :plan.', [
                'name' => $name,
                'plan' => $subscription->plan?->name ?? __('PsiConecta'),
            ]);
    }
}
