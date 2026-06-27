<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\NewConversationMessageNotification;
use App\Notifications\SubscriptionExpiringNotification;
use App\Support\NotificationPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPresenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_presents_subscription_notification_with_title_and_message(): void
    {
        $user = User::factory()->create();

        $user->notify(new SubscriptionExpiringNotification(
            $user->professionalSubscription,
            now()->addDays(2),
            2,
            true,
        ));

        $presented = NotificationPresenter::present($user->notifications()->first());

        $this->assertStringContainsString('teste', strtolower($presented['title']));
        $this->assertNotSame('', $presented['message']);
        $this->assertTrue($presented['is_unread']);
    }

    public function test_presents_conversation_message_with_sender_fallback(): void
    {
        $professional = User::factory()->create();
        $patient = User::factory()->create(['professional_id' => $professional->id]);
        $conversation = app(\App\Services\ConversationService::class)->findOrCreateForUsers($professional, $patient);
        $message = app(\App\Services\ConversationService::class)->sendMessage(
            $conversation,
            $professional,
            'Olá, tudo bem?',
        );

        $patient->notify(new NewConversationMessageNotification($conversation, $message));

        $presented = NotificationPresenter::present($patient->notifications()->first());

        $this->assertStringContainsString($professional->name, $presented['title']);
        $this->assertStringContainsString('Olá', $presented['message']);
    }
}
