<?php

namespace App\Support;

use App\Models\User;

final class PaymentMethodResolution
{
    public const MODE_ASAAS = 'asaas';

    public const MODE_MANUAL = 'manual';

    public const MODE_NOT_CONFIGURED = 'not_configured';

    public function __construct(
        public readonly string $mode,
        public readonly User $payee,
        public readonly ?string $asaasWalletId = null,
        public readonly ?string $pixManualLink = null,
        public readonly ?string $pixQrcodePath = null,
        public readonly ?string $pixQrcodeUrl = null,
    ) {}

    public function isAsaas(): bool
    {
        return $this->mode === self::MODE_ASAAS;
    }

    public function isManual(): bool
    {
        return $this->mode === self::MODE_MANUAL;
    }

    public function isNotConfigured(): bool
    {
        return $this->mode === self::MODE_NOT_CONFIGURED;
    }

    public function hasManualPix(): bool
    {
        return filled($this->pixManualLink) || filled($this->pixQrcodeUrl);
    }

    public function statusBadgeLabel(): string
    {
        return match ($this->mode) {
            self::MODE_ASAAS => __('Recebendo via Asaas'),
            self::MODE_MANUAL => __('Recebendo via PIX manual'),
            default => __('Configuração pendente'),
        };
    }
}
