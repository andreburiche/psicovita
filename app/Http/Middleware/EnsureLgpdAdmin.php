<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLgpdAdmin
{
    /**
     * Painel de gestão de solicitações LGPD (admin ou e-mail do DPO).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if (! $user->canManageLgpdRequests()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Acesso restrito ao encarregado de proteção de dados.'),
                ], 403);
            }

            abort(403, __('Acesso restrito ao encarregado de proteção de dados.'));
        }

        return $next($request);
    }
}
