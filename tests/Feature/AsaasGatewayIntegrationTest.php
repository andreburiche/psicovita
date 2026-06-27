<?php

namespace Tests\Feature;

use App\Enums\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Services\Gateways\AsaasGatewayService;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AsaasGatewayIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'asaas.enabled' => true,
            'asaas.api_key' => 'test_api_key',
            'asaas.base_url' => 'https://sandbox.asaas.com/api/v3',
        ]);
    }

    public function test_real_mode_creates_customer_payment_and_pix(): void
    {
        Http::fake([
            'https://sandbox.asaas.com/api/v3/customers' => Http::response(['id' => 'cus_12345']),
            'https://sandbox.asaas.com/api/v3/payments' => Http::response([
                'id' => 'pay_67890',
                'status' => 'PENDING',
                'invoiceUrl' => 'https://sandbox.asaas.com/i/67890',
            ]),
            'https://sandbox.asaas.com/api/v3/payments/pay_67890/pixQrCode' => Http::response([
                'encodedImage' => base64_encode('png-bytes'),
                'payload' => 'PIXCOPYPASTE123',
                'expirationDate' => '2026-12-31T23:59:59-03:00',
            ]),
        ]);

        $professional = User::factory()->create();
        $patient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'asaas@test.com',
            'email_hash' => ContactHasher::emailHash('asaas@test.com'),
        ]);
        $professional->update(['asaas_wallet_id' => 'wal_prof_01']);
        config(['asaas.split_enabled' => true]);

        $payment = Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Pending,
            'amount' => 150,
            'payment_method' => PaymentMethod::Pix,
            'professional_amount' => 135,
            'platform_fee' => 15,
        ]);

        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'email' => 'asaas@test.com',
        ]);

        $this->actingAs($patientUser);

        $this->post(route('patient.payments.pay', $payment))
            ->assertRedirect(route('patient.payments.show', $payment));

        $payment->refresh();
        $patient->refresh();

        $this->assertSame('cus_12345', $patient->asaas_customer_id);
        $this->assertSame('pay_67890', $payment->external_id);
        $this->assertSame(PaymentGateway::Asaas, $payment->gateway);
        $this->assertSame('PIXCOPYPASTE123', $payment->gateway_meta['pix']['payload'] ?? null);

        Http::assertSent(fn ($request) => $request->method() === 'POST' && str_contains($request->url(), '/customers'));
        Http::assertSent(function ($request) {
            if ($request->method() !== 'POST' || ! str_contains($request->url(), '/payments')) {
                return false;
            }

            $split = $request->data()['split'][0] ?? null;

            return is_array($split)
                && ($split['walletId'] ?? null) === 'wal_prof_01'
                && (float) ($split['fixedValue'] ?? 0) === 135.0;
        });
        Http::assertSent(fn ($request) => $request->method() === 'GET' && str_contains($request->url(), '/pixQrCode'));
    }

    public function test_gateway_service_get_pix_qr_code(): void
    {
        Http::fake([
            'https://sandbox.asaas.com/api/v3/payments/pay_abc/pixQrCode' => Http::response([
                'encodedImage' => 'abc',
                'payload' => 'copy',
                'expirationDate' => '2026-12-31',
            ]),
        ]);

        $pix = app(AsaasGatewayService::class)->getPixQrCode('pay_abc');

        $this->assertSame('abc', $pix['encoded_image']);
        $this->assertSame('copy', $pix['payload']);
    }
}
