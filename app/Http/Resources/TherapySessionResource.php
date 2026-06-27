<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TherapySessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $time = $this->session_time;

        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'professional_id' => $this->professional_id,
            'session_date' => $this->session_date?->format('Y-m-d'),
            'session_time' => $time instanceof \DateTimeInterface ? $time->format('H:i') : substr((string) $time, 0, 5),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'notes' => $this->notes,
            'patient' => PatientResource::make($this->whenLoaded('patient')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
