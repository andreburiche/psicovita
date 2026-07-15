<?php

namespace App\Http\Resources\PatientApi;

use App\Services\PaymentSettingsService;
use App\Support\PaymentMethodResolution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Payment */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pix = $this->gateway_meta['pix'] ?? [];
        $pixPayload = is_array($pix) ? $pix : [];
        $invoiceUrl = $this->gateway_meta['invoice_url']
            ?? $this->gateway_meta['invoiceUrl']
            ?? ($pixPayload['invoice_url'] ?? null);

        $resolution = app(PaymentSettingsService::class)->resolveForPatientProfessional($this->patient);
        $isManual = ($this->gateway_meta['checkout_mode'] ?? null) === PaymentMethodResolution::MODE_MANUAL
            || $resolution->isManual();

        $manualLink = $isManual
            ? ($this->gateway_meta['pix_manual_link'] ?? $resolution->pixManualLink ?? $pixPayload['payload'] ?? null)
            : null;
        $manualQrUrl = $isManual
            ? ($this->gateway_meta['pix_qrcode_url'] ?? $resolution->pixQrcodeUrl ?? $pixPayload['image_url'] ?? null)
            : null;

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'amount' => (int) round((float) $this->amount * 100),
            'amount_formatted' => 'R$ '.number_format((float) $this->amount, 2, ',', '.'),
            'due_date' => $this->therapySession?->session_date?->toDateString(),
            'payment_method' => $this->payment_method?->value,
            'checkout_mode' => $resolution->mode,
            'needs_method_choice' => $this->payment_method === null
                && in_array($this->status->value, ['pending', 'overdue'], true)
                && $resolution->isAsaas(),
            'pix_qr_code' => $isManual ? $manualLink : ($pixPayload['payload'] ?? null),
            'pix_qr_code_image' => $isManual
                ? $manualQrUrl
                : (filled($pixPayload['encoded_image'] ?? null)
                    ? 'data:image/png;base64,'.$pixPayload['encoded_image']
                    : ($pixPayload['image_url'] ?? null)),
            'pix_manual_link' => $manualLink,
            'pix_qrcode_url' => $manualQrUrl,
            'invoice_url' => $invoiceUrl,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'session' => $this->whenLoaded('therapySession', fn () => [
                'id' => $this->therapySession?->id,
                'date' => $this->therapySession?->session_date?->toDateString(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
