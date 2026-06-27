<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupportDeskAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if (! config('psiconecta.chatbot.enabled', true)) {
            abort(404);
        }

        if (! $user->isAdmin() && ! $user->isSupportAgent()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('Acesso restrito ao atendimento.')], 403);
            }

            abort(403, __('Acesso restrito ao atendimento.'));
        }

        return $next($request);
    }
}
