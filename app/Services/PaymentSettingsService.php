<?php

namespace App\Services;

use App\Enums\PaymentMethodPreference;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Support\PaymentMethodResolution;

class PaymentSettingsService
{
    public function practiceOwnerFor(User $user): User
    {
        if ($user->isClinicTeamMember() && $user->clinic_owner_id) {
            return User::query()->find($user->clinic_owner_id) ?? $user;
        }

        return $user;
    }

    public function practiceOwnerForPayment(Payment $payment): ?User
    {
        $payment->loadMissing('patient.professional');

        $professional = $payment->patient?->professional;
        if (! $professional instanceof User) {
            return null;
        }

        return $this->practiceOwnerFor($professional);
    }

    public function resolvePaymentMethodFor(User $user): PaymentMethodResolution
    {
        $payee = $this->practiceOwnerFor($user);

        return $this->resolveForPayee($payee);
    }

    public function resolveForPatientProfessional(?Patient $patient): PaymentMethodResolution
    {
        $professional = $patient?->professional;
        if (! $professional instanceof User) {
            return new PaymentMethodResolution(
                mode: PaymentMethodResolution::MODE_NOT_CONFIGURED,
                payee: new User,
            );
        }

        return $this->resolvePaymentMethodFor($professional);
    }

    public function resolveForPayee(User $payee): PaymentMethodResolution
    {
        $preference = $payee->payment_method_preference instanceof PaymentMethodPreference
            ? $payee->payment_method_preference
            : PaymentMethodPreference::tryFrom((string) ($payee->payment_method_preference ?? 'auto'))
                ?? PaymentMethodPreference::Auto;

        $hasAsaas = $this->hasAsaasConfigured($payee);
        $hasManual = filled($payee->pix_manual_link) || filled($payee->pix_qrcode_path);

        if ($preference === PaymentMethodPreference::Manual) {
            if ($hasManual) {
                return $this->manualResolution($payee);
            }

            return $this->notConfigured($payee);
        }

        if ($preference === PaymentMethodPreference::Asaas) {
            if ($hasAsaas) {
                return $this->asaasResolution($payee);
            }

            return $this->notConfigured($payee);
        }

        // auto: Asaas primeiro; sem Asaas, PIX manual do dono da prática.
        if ($hasAsaas) {
            return $this->asaasResolution($payee);
        }

        if ($hasManual) {
            return $this->manualResolution($payee);
        }

        return $this->notConfigured($payee);
    }

    /**
     * Com split activo, exige carteira do dono. Sem split, a cobrança vai para a conta
     * da plataforma (comportamento histórico) — não bloqueia o checkout clínico.
     */
    private function hasAsaasConfigured(User $payee): bool
    {
        if (filled($payee->asaas_wallet_id)) {
            return true;
        }

        return ! (bool) config('asaas.split_enabled');
    }

    private function asaasResolution(User $payee): PaymentMethodResolution
    {
        return new PaymentMethodResolution(
            mode: PaymentMethodResolution::MODE_ASAAS,
            payee: $payee,
            asaasWalletId: (string) $payee->asaas_wallet_id,
        );
    }

    private function manualResolution(User $payee): PaymentMethodResolution
    {
        return new PaymentMethodResolution(
            mode: PaymentMethodResolution::MODE_MANUAL,
            payee: $payee,
            pixManualLink: $payee->pix_manual_link,
            pixQrcodePath: $payee->pix_qrcode_path,
            pixQrcodeUrl: $payee->pixQrcodeUrl(),
        );
    }

    private function notConfigured(User $payee): PaymentMethodResolution
    {
        return new PaymentMethodResolution(
            mode: PaymentMethodResolution::MODE_NOT_CONFIGURED,
            payee: $payee,
            asaasWalletId: $payee->asaas_wallet_id,
            pixManualLink: $payee->pix_manual_link,
            pixQrcodePath: $payee->pix_qrcode_path,
            pixQrcodeUrl: $payee->pixQrcodeUrl(),
        );
    }
}
