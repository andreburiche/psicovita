<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * @param  array<string, mixed>  $data  name, email, cpf, phone
     */
    public function ensureCustomer(array $data): string;

    /**
     * @param  array<string, mixed>  $data  name, email, phone, cpf_cnpj, external_reference
     */
    public function ensureWallet(array $data): string;

    /**
     * @param  array<string, mixed>  $data  customer_id, amount, billing_type, description, due_date
     * @return array{external_id: string, status: string, raw: array<string, mixed>, pix?: array<string, mixed>|null}
     */
    public function createCharge(array $data): array;

    /**
     * @return array{encoded_image: string, payload: string, expiration_date: string|null, raw: array<string, mixed>}
     */
    public function getPixQrCode(string $chargeExternalId): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array{external_id: string, status: string, raw: array<string, mixed>}
     */
    public function createSubscription(array $data): array;

    public function cancelSubscription(string $externalId): bool;

    /**
     * @param  array<string, mixed>  $payload
     * @return array{event: string, external_id: string|null, status: string|null, raw: array<string, mixed>}
     */
    public function handleWebhook(array $payload): array;
}
