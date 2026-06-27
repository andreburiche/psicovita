<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\AsaasApiException;
use App\Models\User;

class AsaasOnboardingService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    /**
     * @param  array<string, mixed>  $addressData
     */
    public function provisionProfessionalWallet(User $user, ?string $cpfCnpj = null, array $addressData = []): string
    {
        if (! $user->isProfessional()) {
            throw new \InvalidArgumentException(__('Apenas profissionais podem criar carteira de recebimento.'));
        }

        if (filled($user->asaas_wallet_id)) {
            return (string) $user->asaas_wallet_id;
        }

        $cpfDigits = $cpfCnpj !== null ? only_digits($cpfCnpj) : '';

        if (config('asaas.connect_enabled') && config('asaas.enabled')) {
            if (strlen($cpfDigits) < 11) {
                throw new \InvalidArgumentException(__('Informe o CPF ou CNPJ para criar a carteira no Asaas.'));
            }

            $phoneDigits = $user->phone ? only_digits((string) $user->phone) : '';
            if (strlen($phoneDigits) < 10) {
                throw new \InvalidArgumentException(__('Informe um telefone válido no perfil antes de criar a carteira no Asaas.'));
            }

            if (! filled($addressData['postal_code'] ?? null)
                || ! filled($addressData['address'] ?? null)
                || ! filled($addressData['address_number'] ?? null)
                || ! filled($addressData['province'] ?? null)) {
                throw new \InvalidArgumentException(__('Informe CEP, morada, número e bairro para criar a carteira no Asaas.'));
            }
        }

        try {
            $walletId = $this->gateway->ensureWallet(array_filter([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ? only_digits((string) $user->phone) : null,
                'cpf_cnpj' => $cpfDigits !== '' ? $cpfDigits : null,
                'external_reference' => 'user:'.$user->id,
                'postal_code' => $addressData['postal_code'] ?? null,
                'address' => $addressData['address'] ?? null,
                'address_number' => $addressData['address_number'] ?? null,
                'province' => $addressData['province'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''));

            $user->update(['asaas_wallet_id' => $walletId]);

            return $walletId;
        } catch (AsaasApiException $e) {
            throw new \InvalidArgumentException($e->getMessage(), previous: $e);
        }
    }
}
