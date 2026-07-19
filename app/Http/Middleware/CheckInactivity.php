<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckInactivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $timeoutMinutes = max(1, $user->inactivityTimeoutMinutes());
        $lastActivity = $request->session()->get('last_activity_at');

        if (is_numeric($lastActivity)) {
            $idleSeconds = now()->getTimestamp() - (int) $lastActivity;

            if ($idleSeconds > ($timeoutMinutes * 60)) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => __('Sua sessão expirou por inatividade.'),
                    ], 401);
                }

                return redirect()
                    ->guest(route('login'))
                    ->with('status', __('Sua sessão expirou por inatividade.'));
            }
        }

        $request->session()->put('last_activity_at', now()->getTimestamp());

        return $next($request);
    }

    private function shouldSkip(Request $request): bool
    {
        // keep-alive NÃO é ignorado: se já expirou no servidor, não permite "reviver" a sessão.
        return $request->routeIs([
            'session.inactivity-expire',
            'logout',
            'logout.confirm',
        ]);
    }
}
