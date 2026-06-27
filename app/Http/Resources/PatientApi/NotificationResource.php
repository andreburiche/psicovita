<?php

namespace App\Http\Resources\PatientApi;

use App\Support\NotificationPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Illuminate\Notifications\DatabaseNotification */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $presented = NotificationPresenter::present($this->resource);

        return [
            'id' => $this->id,
            'title' => $presented['title'],
            'message' => $presented['message'],
            'tone' => $presented['tone'],
            'icon' => $presented['icon'],
            'is_unread' => $presented['is_unread'],
            'action_url' => $presented['action_url'],
            'created_at' => $presented['created_at']?->toIso8601String(),
        ];
    }
}
