<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'therapy_session_id' => $this->therapy_session_id,
            'amount' => (float) $this->amount,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'payment_method' => $this->payment_method?->value,
            'payment_method_label' => $this->payment_method?->label(),
            'notes' => $this->notes,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'gateway' => $this->gateway?->value,
            'gateway_label' => $this->gateway?->label(),
            'external_id' => $this->external_id,
            'platform_fee' => $this->platform_fee !== null ? (float) $this->platform_fee : null,
            'professional_amount' => $this->professional_amount !== null ? (float) $this->professional_amount : null,
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'patient' => PatientResource::make($this->whenLoaded('patient')),
            'therapy_session' => TherapySessionResource::make($this->whenLoaded('therapySession')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
