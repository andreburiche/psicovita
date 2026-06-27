<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientApi\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        return NotificationResource::collection($notifications)->response();
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()
            ->notifications()
            ->where('id', $notification)
            ->firstOrFail();

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        return response()->json(['message' => __('Notificação marcada como lida.')]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => __('Todas as notificações foram marcadas como lidas.')]);
    }
}
