<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\SubscriptionExpiringNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardMonetizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_pending_payments_alert(): void
    {
        $professional = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'amount' => 150,
        ]);

        $this->actingAs($professional);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Ver financeiro'), false)
            ->assertSee('150,00', false);
    }

    public function test_connect_provision_sends_address_to_asaas(): void
    {
        config([
            'asaas.enabled' => true,
            'asaas.connect_enabled' => true,
            'asaas.split_enabled' => true,
            'asaas.api_key' => 'test_api_key',
            'asaas.base_url' => 'https://sandbox.asaas.com/api/v3',
        ]);

        Http::fake([
            'https://sandbox.asaas.com/api/v3/accounts' => Http::response(['walletId' => 'wal_connect01']),
        ]);

        $user = User::factory()->create(['phone' => '11999998888']);
        $this->actingAs($user);

        $this->post(route('profile.asaas-wallet.provision'), [
            'cpf_cnpj' => '12345678901',
            'postal_code' => '01310-100',
            'address' => 'Av Paulista',
            'address_number' => '1000',
            'province' => 'Bela Vista',
        ])->assertRedirect(route('profile.edit'));

        $this->assertSame('wal_connect01', $user->fresh()->asaas_wallet_id);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/accounts')) {
                return false;
            }

            $data = $request->data();

            return ($data['postalCode'] ?? null) === '01310100'
                && ($data['address'] ?? null) === 'Av Paulista';
        });
    }

    public function test_mark_all_notifications_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new SubscriptionExpiringNotification(
            $user->professionalSubscription,
            now()->addDays(1),
            1,
            true,
        ));

        $this->actingAs($user);

        $this->post(route('notifications.mark-all-read'))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_dashboard_shows_clinical_revenue_split_summary(): void
    {
        $professional = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Paid,
            'amount' => 200,
            'platform_fee' => 20,
            'professional_amount' => 180,
            'paid_at' => now(),
        ]);

        $this->actingAs($professional);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Receita clínica (mês)'), false)
            ->assertSee(__('Seu repasse'), false)
            ->assertSee('200,00', false)
            ->assertSee('180,00', false)
            ->assertSee('20,00', false);
    }

    public function test_admin_dashboard_hides_clinical_financial_widgets(): void
    {
        $admin = User::factory()->create(['role' => \App\Enums\UserRole::Admin]);

        $this->actingAs($admin);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('Receita clínica (mês)'), false)
            ->assertDontSee(__('Ver financeiro'), false)
            ->assertSee(__('Conformidade LGPD'), false);
    }

    public function test_dashboard_kpi_shows_patient_quota_for_limited_plan(): void
    {
        $professional = User::factory()->create();
        $plan = \App\Models\SubscriptionPlan::query()
            ->where('slug', \App\Enums\SubscriptionPlanSlug::Essencial)
            ->firstOrFail();

        $professional->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => \App\Enums\SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->addMonth(),
        ]);

        Patient::factory()->count(12)->create(['professional_id' => $professional->id]);

        $this->actingAs($professional);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__(':count de :limit no plano', ['count' => 12, 'limit' => 50]), false)
            ->assertSee(route('patients.index'), false);
    }
}
