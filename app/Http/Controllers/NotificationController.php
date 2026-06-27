<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function open(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()
            ->notifications()
            ->where('id', $notification)
            ->firstOrFail();

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        $target = data_get($record->data, 'action_url');

        return redirect()->to(filled($target) ? (string) $target : route('dashboard'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()
            ->back()
            ->with('status', __('Todas as notificações foram marcadas como lidas.'));
    }
}
