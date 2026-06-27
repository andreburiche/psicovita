<?php

namespace App\Console\Commands;

use App\Models\ProfessionalSubscription;
use App\Models\User;
use App\Notifications\SubscriptionExpiringNotification;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class SubscriptionRemindersCommand extends Command
{
    protected $signature = 'psiconecta:subscription-reminders';

    protected $description = 'Notifica profissionais quando trial ou assinatura está a terminar.';

    public function handle(SubscriptionService $subscriptions): int
    {
        $sent = 0;

        foreach ($subscriptions->subscriptionsDueForReminder() as $subscription) {
            $user = $subscription->user;
            if (! $user instanceof User) {
                continue;
            }

            $context = $subscriptions->reminderContext($subscription);
            if ($context === null) {
                continue;
            }

            if ($this->alreadyNotifiedToday($user, $subscription, $context['expires_at'])) {
                continue;
            }

            $user->notify(new SubscriptionExpiringNotification(
                $subscription,
                $context['expires_at'],
                $context['days_remaining'],
                $context['is_trial'],
            ));

            $sent++;
        }

        $this->info(__('Lembretes de assinatura enviados: :count', ['count' => $sent]));

        return self::SUCCESS;
    }

    private function alreadyNotifiedToday(User $user, ProfessionalSubscription $subscription, \Illuminate\Support\Carbon $expiresAt): bool
    {
        $type = SubscriptionExpiringNotification::class;

        return $user->notifications()
            ->where('type', $type)
            ->whereDate('created_at', now()->toDateString())
            ->get()
            ->contains(function ($notification) use ($subscription, $expiresAt) {
                $data = $notification->data;

                return (int) data_get($data, 'professional_subscription_id') === $subscription->id
                    && data_get($data, 'expires_on') === $expiresAt->toDateString();
            });
    }
}
