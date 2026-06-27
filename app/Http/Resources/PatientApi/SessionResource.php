<?php

namespace App\Http\Resources\PatientApi;

use App\Services\PatientPortalSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TherapySession */
class SessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $portal = app(PatientPortalSessionService::class);
        $videoCall = $this->videoCall;

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'type' => $this->type->value,
            'session_date' => $this->session_date?->toDateString(),
            'session_time' => is_string($this->session_time)
                ? substr($this->session_time, 0, 5)
                : $this->session_time?->format('H:i'),
            'professional' => $this->whenLoaded('professional', fn () => [
                'id' => $this->professional?->id,
                'name' => $this->professional?->name,
            ]),
            'can_join_now' => $portal->canPatientJoinNow($this->resource),
            'join_status' => $portal->joinStatusLabel($this->resource),
            'jitsi_domain' => config('psiconecta.video_conference.jitsi_domain', 'meet.jit.si'),
            'room_name' => $videoCall?->room_name,
            'video_status' => $videoCall?->status?->value,
        ];
    }
}
