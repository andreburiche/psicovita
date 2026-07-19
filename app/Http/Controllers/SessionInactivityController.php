<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SessionInactivityController extends Controller
{
    public function keepAlive(Request $request): Response
    {
        $request->session()->put('last_activity_at', now()->getTimestamp());

        return response()->noContent();
    }

    public function expire(Request $request): RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', __('Sua sessão expirou por inatividade.'));
    }
}
