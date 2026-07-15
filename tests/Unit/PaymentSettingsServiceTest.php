<?php

namespace Tests\Unit;

use App\Enums\PaymentMethodPreference;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use App\Services\PaymentSettingsService;
use App\Support\PaymentMethodResolution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PaymentSettingsService::class);
    }

    public function test_autonomous_owner_with_asaas_auto_resolves_asaas(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::Professional,
            'asaas_wallet_id' => 'wal_test1234567890',
            'payment_method_preference' => PaymentMethodPreference::Auto,
        ]);

        $resolution = $this->service->resolvePaymentMethodFor($owner);

        $this->assertTrue($resolution->isAsaas());
        $this->assertSame('wal_test1234567890', $resolution->asaasWalletId);
        $this->assertTrue($resolution->payee->is($owner));
    }

    public function test_autonomous_owner_without_asaas_auto_falls_back_to_manual(): void
    {
        config(['asaas.split_enabled' => true]);

        $owner = User::factory()->create([
            'role' => UserRole::Professional,
            'asaas_wallet_id' => null,
            'pix_manual_link' => 'chave-pix@email.com',
            'payment_method_preference' => PaymentMethodPreference::Auto,
        ]);

        $resolution = $this->service->resolvePaymentMethodFor($owner);

        $this->assertTrue($resolution->isManual());
        $this->assertSame('chave-pix@email.com', $resolution->pixManualLink);
    }

    public function test_without_wallet_and_split_disabled_auto_uses_platform_asaas(): void
    {
        config(['asaas.split_enabled' => false]);

        $owner = User::factory()->create([
            'role' => UserRole::Professional,
            'asaas_wallet_id' => null,
            'pix_manual_link' => 'chave-pix@email.com',
            'payment_method_preference' => PaymentMethodPreference::Auto,
        ]);

        $resolution = $this->service->resolvePaymentMethodFor($owner);

        $this->assertTrue($resolution->isAsaas());
    }

    public function test_forced_manual_wins_even_with_wallet(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::Professional,
            'asaas_wallet_id' => 'wal_test1234567890',
            'pix_manual_link' => 'https://pay.example/pix',
            'payment_method_preference' => PaymentMethodPreference::Manual,
        ]);

        $resolution = $this->service->resolvePaymentMethodFor($owner);

        $this->assertTrue($resolution->isManual());
        $this->assertSame('https://pay.example/pix', $resolution->pixManualLink);
    }

    public function test_forced_asaas_without_wallet_is_not_configured(): void
    {
        config(['asaas.split_enabled' => true]);

        $owner = User::factory()->create([
            'role' => UserRole::Professional,
            'asaas_wallet_id' => null,
            'pix_manual_link' => 'chave@pix.com',
            'payment_method_preference' => PaymentMethodPreference::Asaas,
        ]);

        $resolution = $this->service->resolvePaymentMethodFor($owner);

        $this->assertTrue($resolution->isNotConfigured());
        $this->assertSame(PaymentMethodResolution::MODE_NOT_CONFIGURED, $resolution->mode);
    }

    public function test_team_member_resolves_owner_settings(): void
    {
        config(['asaas.split_enabled' => true]);

        $owner = User::factory()->create([
            'role' => UserRole::Professional,
            'asaas_wallet_id' => null,
            'pix_manual_link' => 'owner-pix-key',
            'payment_method_preference' => PaymentMethodPreference::Auto,
        ]);

        $member = User::factory()->create([
            'role' => UserRole::Professional,
            'clinic_owner_id' => $owner->id,
            'asaas_wallet_id' => 'wal_member_should_ignore',
            'pix_manual_link' => 'member-should-ignore',
            'payment_method_preference' => PaymentMethodPreference::Asaas,
        ]);

        $patient = Patient::factory()->create([
            'professional_id' => $owner->id,
        ]);

        $resolutionFromMember = $this->service->resolvePaymentMethodFor($member);
        $resolutionFromPatient = $this->service->resolveForPatientProfessional($patient);

        $this->assertTrue($resolutionFromMember->isManual());
        $this->assertSame('owner-pix-key', $resolutionFromMember->pixManualLink);
        $this->assertTrue($resolutionFromMember->payee->is($owner));

        $this->assertTrue($resolutionFromPatient->isManual());
        $this->assertSame('owner-pix-key', $resolutionFromPatient->pixManualLink);
    }
}
