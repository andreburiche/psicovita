<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Str;

class AsaasGatewayService implements PaymentGatewayInterface
{
    public function __construct(
        private readonly AsaasApiClient $client,
    ) {}

    public function ensureCustomer(array $data): string
    {
        if (! $this->client->isConfigured()) {
            return 'cus_stub_'.Str::lower(Str::random(12));
        }

        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'cpfCnpj' => $data['cpf'] ?? null,
            'mobilePhone' => $data['phone'] ?? null,
            'externalReference' => isset($data['external_reference']) ? (string) $data['external_reference'] : null,
        ], fn ($value) => $value !== null && $value !== '');

        $response = $this->client->post('/customers', $payload);

        return (string) ($response['id'] ?? throw new \RuntimeException('Asaas customer id missing'));
    }

    public function ensureWallet(array $data): string
    {
        if (! $this->client->isConfigured() || ! config('asaas.connect_enabled')) {
            return 'wal_stub_'.Str::lower(Str::random(12));
        }

        $cpfCnpj = (string) ($data['cpf_cnpj'] ?? '');
        if ($cpfCnpj === '') {
            throw new \InvalidArgumentException('cpf_cnpj is required for Asaas wallet.');
        }

        $defaults = config('asaas.connect_defaults', []);
        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'cpfCnpj' => $cpfCnpj,
            'mobilePhone' => $data['phone'] ?? null,
            'birthDate' => $data['birth_date'] ?? ($defaults['birth_date'] ?? '1990-01-01'),
            'companyType' => $data['company_type'] ?? ($defaults['company_type'] ?? 'INDIVIDUAL'),
            'incomeValue' => isset($data['income_value']) ? (float) $data['income_value'] : (float) ($defaults['income_value'] ?? 5000),
            'address' => $data['address'] ?? ($defaults['address'] ?? null),
            'addressNumber' => $data['address_number'] ?? ($defaults['address_number'] ?? null),
            'province' => $data['province'] ?? ($defaults['province'] ?? null),
            'postalCode' => isset($data['postal_code']) ? only_digits((string) $data['postal_code']) : ($defaults['postal_code'] ?? null),
            'externalReference' => isset($data['external_reference']) ? (string) $data['external_reference'] : null,
        ], fn ($value) => $value !== null && $value !== '');

        $response = $this->client->post('/accounts', $payload);

        return (string) ($response['walletId'] ?? throw new \RuntimeException('Asaas wallet id missing'));
    }

    public function createCharge(array $data): array
    {
        if (! $this->client->isConfigured()) {
            return $this->stubCharge($data);
        }

        $customerId = (string) ($data['customer_id'] ?? '');
        if ($customerId === '') {
            throw new \InvalidArgumentException('customer_id is required for Asaas charge.');
        }

        $payload = [
            'customer' => $customerId,
            'billingType' => (string) ($data['billing_type'] ?? 'PIX'),
            'value' => round((float) ($data['amount'] ?? 0), 2),
            'dueDate' => (string) ($data['due_date'] ?? now()->addDays(3)->toDateString()),
            'description' => (string) ($data['description'] ?? __('Cobrança PsiConecta')),
        ];

        $split = is_array($data['split'] ?? null) ? $data['split'] : null;
        if ($split !== null && filled($split['wallet_id'] ?? null) && (float) ($split['fixed_value'] ?? 0) > 0) {
            $payload['split'] = [[
                'walletId' => (string) $split['wallet_id'],
                'fixedValue' => round((float) $split['fixed_value'], 2),
            ]];
        }

        $response = $this->client->post('/payments', $payload);
        $externalId = (string) ($response['id'] ?? '');

        $pix = null;
        if ($externalId !== '' && ($payload['billingType'] === 'PIX')) {
            $pix = $this->normalizePixResponse($this->client->get('/payments/'.$externalId.'/pixQrCode'));
        }

        return [
            'external_id' => $externalId,
            'status' => (string) ($response['status'] ?? 'PENDING'),
            'raw' => $response,
            'pix' => $pix,
        ];
    }

    public function getPixQrCode(string $chargeExternalId): array
    {
        if (! $this->client->isConfigured()) {
            return $this->stubPix($chargeExternalId);
        }

        return $this->normalizePixResponse(
            $this->client->get('/payments/'.$chargeExternalId.'/pixQrCode')
        );
    }

    public function createSubscription(array $data): array
    {
        $billingType = (string) ($data['billing_type'] ?? 'PIX');

        if (! $this->client->isConfigured()) {
            return $this->stubSubscription($billingType, $data);
        }

        $response = $this->client->post('/subscriptions', array_filter([
            'customer' => $data['customer_id'] ?? null,
            'billingType' => $billingType,
            'value' => isset($data['amount']) ? round((float) $data['amount'], 2) : null,
            'cycle' => $data['cycle'] ?? 'MONTHLY',
            'description' => $data['description'] ?? 'PsiConecta',
            'nextDueDate' => $data['next_due_date'] ?? now()->addDay()->toDateString(),
        ]));

        $externalId = (string) ($response['id'] ?? '');
        $firstPayment = $externalId !== '' ? $this->getFirstPendingSubscriptionPayment($externalId, $billingType) : null;

        return [
            'external_id' => $externalId,
            'status' => (string) ($response['status'] ?? 'ACTIVE'),
            'raw' => $response,
            'first_payment' => $firstPayment,
        ];
    }

    public function getFirstPendingSubscriptionPayment(string $subscriptionExternalId, ?string $billingType = null): ?array
    {
        if (! $this->client->isConfigured()) {
            return null;
        }

        $response = $this->client->get('/subscriptions/'.$subscriptionExternalId.'/payments?status=PENDING&limit=1');
        $payment = is_array($response['data'] ?? null) ? ($response['data'][0] ?? null) : null;

        if (! is_array($payment)) {
            return null;
        }

        $externalId = (string) ($payment['id'] ?? '');
        if ($externalId === '') {
            return null;
        }

        $billingType ??= (string) ($payment['billingType'] ?? 'PIX');
        $pix = null;
        if ($billingType === 'PIX') {
            $pix = $this->normalizePixResponse($this->client->get('/payments/'.$externalId.'/pixQrCode'));
        }

        return [
            'external_id' => $externalId,
            'invoice_url' => $payment['invoiceUrl'] ?? null,
            'pix' => $pix,
            'raw' => $payment,
        ];
    }

    public function cancelSubscription(string $externalId): bool
    {
        if (! $this->client->isConfigured()) {
            return $externalId !== '';
        }

        $this->client->post('/subscriptions/'.$externalId.'/cancel', []);

        return true;
    }

    public function handleWebhook(array $payload): array
    {
        $event = (string) ($payload['event'] ?? 'UNKNOWN');
        $payment = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
        $subscription = is_array($payload['subscription'] ?? null) ? $payload['subscription'] : [];

        $subscriptionExternalId = isset($payment['subscription'])
            ? (string) $payment['subscription']
            : (isset($subscription['id']) ? (string) $subscription['id'] : null);

        return [
            'event' => $event,
            'external_id' => isset($payment['id']) ? (string) $payment['id'] : null,
            'subscription_external_id' => $subscriptionExternalId,
            'status' => isset($payment['status']) ? (string) $payment['status'] : null,
            'raw' => $payload,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{external_id: string, status: string, raw: array<string, mixed>, first_payment: array<string, mixed>|null}
     */
    private function stubSubscription(string $billingType, array $data): array
    {
        $externalId = 'asaas_sub_'.Str::lower(Str::random(16));
        $firstPaymentId = 'asaas_chg_'.Str::lower(Str::random(16));

        return [
            'external_id' => $externalId,
            'status' => 'ACTIVE',
            'raw' => [
                'id' => $externalId,
                'billingType' => $billingType,
                'value' => $data['amount'] ?? null,
                'status' => 'ACTIVE',
                'stub' => true,
            ],
            'first_payment' => [
                'external_id' => $firstPaymentId,
                'invoice_url' => $billingType === 'CREDIT_CARD'
                    ? 'https://sandbox.asaas.com/i/'.$firstPaymentId
                    : null,
                'pix' => $billingType === 'PIX' ? $this->stubPix($firstPaymentId) : null,
                'raw' => ['id' => $firstPaymentId, 'stub' => true],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{external_id: string, status: string, raw: array<string, mixed>, pix: array<string, mixed>}
     */
    private function stubCharge(array $data): array
    {
        $externalId = 'asaas_chg_'.Str::lower(Str::random(16));
        $billingType = (string) ($data['billing_type'] ?? 'PIX');

        return [
            'external_id' => $externalId,
            'status' => 'PENDING',
            'raw' => [
                'id' => $externalId,
                'value' => $data['amount'] ?? null,
                'billingType' => $billingType,
                'status' => 'PENDING',
                'invoiceUrl' => $billingType === 'CREDIT_CARD'
                    ? 'https://sandbox.asaas.com/i/'.$externalId
                    : null,
                'stub' => true,
            ],
            'pix' => $billingType === 'PIX' ? $this->stubPix($externalId) : null,
        ];
    }

    /**
     * @return array{encoded_image: string, payload: string, expiration_date: string|null, raw: array<string, mixed>}
     */
    private function stubPix(string $chargeExternalId): array
    {
        $static = $this->staticPixFallback();

        if ($static !== null) {
            return $static;
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="220" height="220"><rect width="220" height="220" fill="#f8fafc"/><text x="110" y="110" text-anchor="middle" font-size="14" fill="#64748b">PIX stub</text></svg>';

        return [
            'encoded_image' => base64_encode($svg),
            'image_mime' => 'image/svg+xml',
            'payload' => '00020126STUB'.strtoupper(substr($chargeExternalId, -8)),
            'expiration_date' => now()->addDay()->endOfDay()->toIso8601String(),
            'raw' => ['stub' => true],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function staticPixFallback(): ?array
    {
        $relativePath = (string) config('asaas.pix_fallback_image', '');

        if ($relativePath === '') {
            return null;
        }

        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $absolutePath = public_path($relativePath);

        if (! is_file($absolutePath)) {
            return null;
        }

        $payload = config('asaas.pix_fallback_payload');
        $bankLabel = config('asaas.pix_fallback_bank');

        return array_filter([
            'image_url' => asset($relativePath),
            'payload' => filled($payload) ? (string) $payload : null,
            'bank_label' => filled($bankLabel) ? (string) $bankLabel : null,
            'expiration_date' => null,
            'raw' => ['stub' => true, 'static_fallback' => true],
        ], fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array{encoded_image: string, payload: string, expiration_date: string|null, raw: array<string, mixed>}
     */
    private function normalizePixResponse(array $response): array
    {
        return [
            'encoded_image' => (string) ($response['encodedImage'] ?? ''),
            'image_mime' => 'image/png',
            'payload' => (string) ($response['payload'] ?? ''),
            'expiration_date' => isset($response['expirationDate']) ? (string) $response['expirationDate'] : null,
            'raw' => $response,
        ];
    }
}
