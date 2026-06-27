<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePatientApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->usesPatientPortalExperience()) {
            return response()->json([
                'message' => __('Acesso restrito ao portal do paciente.'),
            ], 403);
        }

        return $next($request);
    }
}
