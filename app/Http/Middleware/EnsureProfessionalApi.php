<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfessionalApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isProfessional()) {
            return response()->json([
                'message' => 'Acesso restrito a profissionais autenticados.',
            ], 403);
        }

        return $next($request);
    }
}
