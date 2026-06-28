<?php

namespace Tests\Feature;

use App\Enums\BillingCycle;
use App\Enums\PaymentMethod;
use App\Enums\SubscriptionPlanSlug;
use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionExpiringNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_with_active_trial_can_create_patient(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('patients.store'), [
            'name' => 'Paciente Assinatura',
            'email' => 'assinatura@example.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'professional_id' => $user->id,
            'name' => 'Paciente Assinatura',
        ]);
    }

    public function test_expired_subscription_blocks_clinical_area(): void
    {
        $user = User::factory()->create();
        $subscription = $user->professionalSubscription;
        $this->assertNotNull($subscription);

        $subscription->update([
            'status' => SubscriptionStatus::Expired,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($user);

        $this->post(route('patients.store'), [
            'name' => 'Bloqueado',
        ])->assertRedirect(route('subscription.checkout'));

        $this->get(route('patients.index'))
            ->assertRedirect(route('subscription.checkout'))
            ->assertSessionHas('subscription_blocked');

        $this->get(route('subscription.checkout'))->assertOk();
    }

    public function test_essencial_plan_blocks_ai_but_allows_patient_create(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Essencial)->firstOrFail();

        $user->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user);

        $this->post(route('patients.store'), [
            'name' => 'Paciente Essencial',
        ])->assertRedirect();

        $this->get(route('ai.index'))
            ->assertRedirect()
            ->assertSessionHas('subscription_blocked');

        $this->post(route('ai.transcribe'), [
            'audio' => \Illuminate\Http\UploadedFile::fake()->create('audio.mp3', 100, 'audio/mpeg'),
            'session_type' => 'retorno',
            'lgpd_audio_consent' => '1',
        ])->assertRedirect()
            ->assertSessionHas('subscription_blocked');
    }

    public function test_essencial_plan_blocks_patient_create_at_limit(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Essencial)->firstOrFail();
        $plan->update(['max_patients' => 2]);

        $user->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->addMonth(),
        ]);

        \App\Models\Patient::factory()->count(2)->create(['professional_id' => $user->id]);

        $this->actingAs($user);

        $this->post(route('patients.store'), [
            'name' => 'Paciente Extra',
        ])->assertForbidden();
    }

    public function test_premium_plan_allows_unlimited_patients(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Premium)->firstOrFail();

        $user->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->addMonth(),
        ]);

        \App\Models\Patient::factory()->count(3)->create(['professional_id' => $user->id]);

        $this->actingAs($user);

        $this->post(route('patients.store'), [
            'name' => 'Paciente Premium',
        ])->assertRedirect();
    }

    public function test_trial_plan_blocks_patient_create_at_ten_patients(): void
    {
        $user = User::factory()->create();
        \App\Models\Patient::factory()->count(10)->create(['professional_id' => $user->id]);

        $this->actingAs($user);

        $this->post(route('patients.store'), [
            'name' => 'Paciente Trial Extra',
        ])->assertForbidden();
    }

    public function test_patients_index_shows_quota_when_limited(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Essencial)->firstOrFail();
        $plan->update(['max_patients' => 2]);

        $user->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->addMonth(),
        ]);

        \App\Models\Patient::factory()->count(2)->create(['professional_id' => $user->id]);

        $this->actingAs($user);

        $this->get(route('patients.index'))
            ->assertOk()
            ->assertSee(__('Limite do plano atingido: :count de :limit pacientes.', ['count' => 2, 'limit' => 2]), false)
            ->assertSee(__('Actualizar plano'), false)
            ->assertDontSee(route('patients.create'), false);
    }

    public function test_patient_quota_context_marks_near_limit(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Essencial)->firstOrFail();

        $user->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->addMonth(),
        ]);

        \App\Models\Patient::factory()->count(46)->create(['professional_id' => $user->id]);

        $quota = app(\App\Services\SubscriptionService::class)->patientQuotaContext($user);

        $this->assertTrue($quota['limited']);
        $this->assertTrue($quota['near_limit']);
        $this->assertSame(4, $quota['remaining']);
    }

    public function test_admin_is_exempt_from_subscription_checks(): void
    {
        $admin = User::factory()->create(['role' => \App\Enums\UserRole::Admin]);
        $service = app(\App\Services\SubscriptionService::class);

        $this->assertTrue($service->isActive($admin));
        $this->assertTrue($service->canUseFeature($admin, 'create_patient'));
        $this->assertTrue($service->canUseFeature($admin, 'use_ai'));
    }

    public function test_profile_shows_subscription_section_for_professional(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(__('Assinatura da plataforma'), false)
            ->assertSee(route('subscription.checkout'), false);
    }

    public function test_professional_can_checkout_subscription_with_pix(): void
    {
        config(['subscription.require_admin_after_payment' => true]);

        $user = User::factory()->create();
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Essencial)->firstOrFail();

        $this->actingAs($user);

        $this->get(route('subscription.checkout'))
            ->assertOk()
            ->assertSee($plan->name, false);

        $this->post(route('subscription.checkout.store'), [
            'subscription_plan_id' => $plan->id,
            'payment_method' => PaymentMethod::Pix->value,
            'billing_cycle' => BillingCycle::Monthly->value,
        ])->assertRedirect(route('subscription.checkout').'#pix-checkout')
            ->assertSessionHas('status');

        $subscription = $user->fresh()->professionalSubscription;
        $this->assertSame($plan->id, $subscription->subscription_plan_id);
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->status);
        $this->assertTrue($subscription->hasPaymentConfirmation());
        $this->assertTrue($subscription->isAwaitingAdminValidation());
        $this->assertNotNull($subscription->gateway_external_id);
        $pix = $subscription->gateway_meta['pix'] ?? [];
        $this->assertTrue(\App\Support\PixCheckout::isDisplayable($pix));

        $this->get(route('subscription.checkout'))
            ->assertOk()
            ->assertSee(__('Pagar com PIX'), false)
            ->assertSee(__('Pagamento manual'), false);
    }

    public function test_checkout_refreshes_legacy_stub_pix_from_env_fallback(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Essencial)->firstOrFail();

        $user->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'gateway_external_id' => 'asaas_sub_legacy01',
            'gateway_meta' => [
                'payment_method' => PaymentMethod::Pix->value,
                'billing_type' => 'PIX',
                'stub' => true,
                'first_payment_external_id' => 'asaas_chg_legacy01',
                'pix' => [
                    'encoded_image' => base64_encode('<svg>stub</svg>'),
                    'image_mime' => 'image/svg+xml',
                    'payload' => '00020126STUBLEGACY1',
                    'raw' => ['stub' => true],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('subscription.checkout'))
            ->assertOk()
            ->assertDontSee('00020126STUBLEGACY1', false)
            ->assertSee(__('Pagamento manual'), false);

        $pix = $user->fresh()->professionalSubscription->gateway_meta['pix'] ?? [];
        $this->assertTrue((bool) ($pix['raw']['static_fallback'] ?? false));
    }

    public function test_professional_can_checkout_yearly_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Essencial)->firstOrFail();

        $this->actingAs($user);

        $this->post(route('subscription.checkout.store'), [
            'subscription_plan_id' => $plan->id,
            'payment_method' => PaymentMethod::Pix->value,
            'billing_cycle' => BillingCycle::Yearly->value,
        ])->assertRedirect(route('subscription.checkout').'#pix-checkout');

        $subscription = $user->fresh()->professionalSubscription;
        $this->assertSame(BillingCycle::Yearly->value, $subscription->gateway_meta['billing_cycle'] ?? null);
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->status);
        $this->assertTrue($subscription->hasPaymentConfirmation());
        $this->assertNull($subscription->ends_at);
    }

    public function test_yearly_subscription_webhook_extends_by_one_year(): void
    {
        config(['asaas.webhook_token' => 'sub-token']);

        $user = User::factory()->create();
        $endsAtBefore = now()->addMonths(2)->startOfSecond();
        $user->professionalSubscription->update([
            'status' => SubscriptionStatus::Active,
            'gateway_external_id' => 'asaas_sub_yearly01',
            'ends_at' => $endsAtBefore,
            'trial_ends_at' => null,
            'gateway_meta' => ['billing_cycle' => BillingCycle::Yearly->value],
        ]);

        $this->postJson(route('webhooks.asaas'), [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_yearly_renew01',
                'subscription' => 'asaas_sub_yearly01',
                'status' => 'RECEIVED',
            ],
        ], ['asaas-access-token' => 'sub-token'])->assertOk();

        $endsAtAfter = $user->fresh()->professionalSubscription->ends_at;
        $this->assertNotNull($endsAtAfter);
        $this->assertTrue($endsAtBefore->copy()->addYear()->equalTo($endsAtAfter));
    }

    public function test_connect_provision_requires_phone_when_connect_enabled(): void
    {
        config([
            'asaas.enabled' => true,
            'asaas.connect_enabled' => true,
            'asaas.split_enabled' => true,
            'asaas.api_key' => 'test_api_key',
        ]);

        $user = User::factory()->create(['phone' => null]);
        $this->actingAs($user);

        $this->post(route('profile.asaas-wallet.provision'), [
            'cpf_cnpj' => '12345678901',
            'postal_code' => '01310-100',
            'address' => 'Av Paulista',
            'address_number' => '1000',
            'province' => 'Bela Vista',
        ])->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('asaas_wallet');

        $this->assertNull($user->fresh()->asaas_wallet_id);
    }

    public function test_professional_can_cancel_gateway_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Essencial)->firstOrFail();

        $user->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->addMonth(),
            'gateway_external_id' => 'asaas_sub_cancel01',
        ]);

        $this->actingAs($user);

        $this->delete(route('subscription.checkout.cancel'))
            ->assertRedirect(route('subscription.checkout'))
            ->assertSessionHas('status');

        $subscription = $user->fresh()->professionalSubscription;
        $this->assertNotNull($subscription->cancelled_at);
        $this->assertTrue(app(\App\Services\SubscriptionService::class)->isActive($user));
    }

    public function test_upgrade_plan_cancels_previous_gateway_subscription(): void
    {
        $user = User::factory()->create();
        $essencial = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Essencial)->firstOrFail();
        $premium = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Premium)->firstOrFail();

        $this->actingAs($user);

        $this->post(route('subscription.checkout.store'), [
            'subscription_plan_id' => $essencial->id,
            'payment_method' => PaymentMethod::Pix->value,
            'billing_cycle' => BillingCycle::Monthly->value,
        ])->assertRedirect(route('subscription.checkout').'#pix-checkout');

        $firstGatewayId = $user->fresh()->professionalSubscription->gateway_external_id;
        $this->assertNotNull($firstGatewayId);

        $this->post(route('subscription.checkout.store'), [
            'subscription_plan_id' => $premium->id,
            'payment_method' => PaymentMethod::Card->value,
            'billing_cycle' => BillingCycle::Monthly->value,
        ])->assertRedirect(route('subscription.checkout').'#pix-checkout');

        $subscription = $user->fresh()->professionalSubscription;
        $this->assertSame($premium->id, $subscription->subscription_plan_id);
        $this->assertNotSame($firstGatewayId, $subscription->gateway_external_id);
    }

    public function test_subscription_webhook_renewal_is_idempotent(): void
    {
        config(['asaas.webhook_token' => 'sub-token']);

        $user = User::factory()->create();
        $endsAtBefore = now()->addDays(5)->startOfSecond();
        $user->professionalSubscription->update([
            'status' => SubscriptionStatus::Active,
            'gateway_external_id' => 'asaas_sub_renew01',
            'ends_at' => $endsAtBefore,
            'trial_ends_at' => null,
            'gateway_meta' => ['last_renewal_payment_id' => 'pay_renew_dup'],
        ]);

        $payload = [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_renew_dup',
                'subscription' => 'asaas_sub_renew01',
                'status' => 'RECEIVED',
            ],
        ];

        $this->postJson(route('webhooks.asaas'), $payload, ['asaas-access-token' => 'sub-token'])->assertOk();

        $endsAtAfter = $user->fresh()->professionalSubscription->ends_at;
        $this->assertNotNull($endsAtAfter);
        $this->assertTrue($endsAtBefore->equalTo($endsAtAfter));
    }

    public function test_subscription_webhook_marks_overdue(): void
    {
        config(['asaas.webhook_token' => 'sub-token']);

        $user = User::factory()->create();
        $user->professionalSubscription->update([
            'status' => SubscriptionStatus::Active,
            'gateway_external_id' => 'asaas_sub_overdue01',
            'ends_at' => now()->addMonth(),
            'trial_ends_at' => null,
        ]);

        $this->postJson(route('webhooks.asaas'), [
            'event' => 'PAYMENT_OVERDUE',
            'payment' => [
                'id' => 'pay_overdue01',
                'subscription' => 'asaas_sub_overdue01',
                'status' => 'OVERDUE',
            ],
        ], ['asaas-access-token' => 'sub-token'])->assertOk();

        $this->assertSame(
            SubscriptionStatus::PastDue,
            $user->fresh()->professionalSubscription->status
        );
    }

    public function test_expire_subscriptions_command_marks_past_due_periods(): void
    {
        $user = User::factory()->create();
        $user->professionalSubscription->update([
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->subDay(),
        ]);

        $this->artisan('psiconecta:expire-subscriptions')->assertSuccessful();

        $this->assertSame(
            SubscriptionStatus::Expired,
            $user->fresh()->professionalSubscription->status
        );
    }

    public function test_professional_can_save_asaas_wallet_id_when_split_enabled(): void
    {
        config(['asaas.split_enabled' => true]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'professional_function' => $user->professional_function->value,
            'asaas_wallet_id' => 'wal_testwallet01',
        ])->assertRedirect(route('profile.edit'));

        $this->assertSame('wal_testwallet01', $user->fresh()->asaas_wallet_id);
    }

    public function test_provision_asaas_wallet_in_stub_mode(): void
    {
        config(['asaas.split_enabled' => true, 'asaas.connect_enabled' => false]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('profile.asaas-wallet.provision'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status');

        $this->assertNotNull($user->fresh()->asaas_wallet_id);
        $this->assertStringStartsWith('wal_stub_', $user->fresh()->asaas_wallet_id);
    }

    public function test_subscription_reminders_command_notifies_expiring_trial(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->professionalSubscription->update([
            'status' => SubscriptionStatus::Trialing,
            'trial_ends_at' => now()->addDays(2),
        ]);

        $this->artisan('psiconecta:subscription-reminders')->assertSuccessful();

        Notification::assertSentTo($user, SubscriptionExpiringNotification::class);
    }

    public function test_dashboard_shows_subscription_renew_cta_when_expiring(): void
    {
        $user = User::factory()->create();
        $user->professionalSubscription->update([
            'trial_ends_at' => now()->addDays(2),
        ]);

        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Renovar assinatura'), false)
            ->assertSee(route('subscription.checkout'), false);
    }

    public function test_dashboard_shows_patient_limit_banner_when_at_quota(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Essencial)->firstOrFail();
        $plan->update(['max_patients' => 2]);

        $user->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->addMonths(2),
        ]);

        \App\Models\Patient::factory()->count(2)->create(['professional_id' => $user->id]);

        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Limite de pacientes atingido (:count/:limit). Actualize o plano para registar novos utentes.', [
                'count' => 2,
                'limit' => 2,
            ]), false)
            ->assertSee(__('Ver planos'), false);
    }

    public function test_sidebar_shows_patient_quota_badge(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Essencial)->firstOrFail();

        $user->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->addMonth(),
        ]);

        \App\Models\Patient::factory()->count(12)->create(['professional_id' => $user->id]);

        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('12/50', false);
    }

    public function test_subscription_webhook_waits_for_admin_before_activation(): void
    {
        config([
            'asaas.webhook_token' => 'sub-token',
            'subscription.require_admin_after_payment' => true,
        ]);

        $user = User::factory()->create();
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Premium)->firstOrFail();

        $user->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::PastDue,
            'gateway_external_id' => 'asaas_sub_webhook01',
            'ends_at' => null,
            'trial_ends_at' => null,
        ]);

        $this->postJson(route('webhooks.asaas'), [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_sub_01',
                'subscription' => 'asaas_sub_webhook01',
                'status' => 'RECEIVED',
            ],
        ], ['asaas-access-token' => 'sub-token'])->assertOk();

        $subscription = $user->fresh()->professionalSubscription;
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->status);
        $this->assertTrue($subscription->hasPaymentConfirmation());
        $this->assertTrue($subscription->isAwaitingAdminValidation());
        $this->assertNull($subscription->ends_at);
    }
}
