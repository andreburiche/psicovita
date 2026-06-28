<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlanSlug;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionPaymentConfirmedAdminNotification;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminProfessionalSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_subscriptions_panel(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($professional)
            ->get(route('admin.subscriptions.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_subscriptions_registry(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $professional = User::factory()->create([
            'role' => UserRole::Professional,
            'name' => 'Dr. Assinatura Demo',
        ]);

        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Premium)->firstOrFail();
        $professional->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'ends_at' => now()->addMonth(),
            'trial_ends_at' => null,
            'gateway_meta' => [
                'payment_method' => 'pix',
                'billing_cycle' => 'monthly',
                'last_renewal_at' => now()->subDay()->toIso8601String(),
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.subscriptions.index', ['q' => 'Assinatura Demo']))
            ->assertOk()
            ->assertSee('Dr. Assinatura Demo')
            ->assertSee('Premium')
            ->assertSee(__('Ativa'));
    }

    public function test_admin_is_notified_when_subscription_payment_is_confirmed(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Essencial)->firstOrFail();
        $professional->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::PastDue,
            'gateway_external_id' => 'sub_test_123',
            'gateway_meta' => [
                'billing_cycle' => 'monthly',
            ],
        ]);

        app(SubscriptionService::class)->confirmFromWebhook([
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_abc',
                'subscription' => 'sub_test_123',
                'status' => 'RECEIVED',
            ],
        ]);

        Notification::assertSentTo(
            $admin,
            SubscriptionPaymentConfirmedAdminNotification::class,
            fn (SubscriptionPaymentConfirmedAdminNotification $notification) => $notification->subscription->user_id === $professional->id
                && $notification->isRenewal === false,
        );
    }

    public function test_admin_can_manually_confirm_subscription_payment(): void
    {
        config(['subscription.require_admin_after_payment' => true]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Premium)->firstOrFail();

        $professional->professionalSubscription->update([
            'status' => SubscriptionStatus::PastDue,
            'gateway_meta' => [
                'payment_confirmed_at' => now()->toIso8601String(),
                'billing_cycle' => 'monthly',
            ],
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.subscriptions.update', $professional->professionalSubscription), [
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'note' => 'PIX recebido manualmente',
            ])
            ->assertRedirect(route('admin.subscriptions.index'))
            ->assertSessionHas('status');

        $subscription = $professional->fresh()->professionalSubscription;
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertSame($plan->id, $subscription->subscription_plan_id);
        $this->assertTrue($subscription->isManuallyValidated());
        $this->assertNotNull($subscription->ends_at);
        $this->assertTrue(app(SubscriptionService::class)->isActive($professional));
    }

    public function test_admin_cannot_confirm_without_payment(): void
    {
        config(['subscription.require_admin_after_payment' => true]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Premium)->firstOrFail();

        $professional->professionalSubscription->update([
            'status' => SubscriptionStatus::Expired,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.subscriptions.update', $professional->professionalSubscription), [
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('manual');

        $this->assertSame(SubscriptionStatus::Expired, $professional->fresh()->professionalSubscription->status);
    }
}
