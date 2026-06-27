<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePatientPortal
{
    /**
     * Rotas do titular no portal do paciente (direitos LGPD, exportação).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if (! $user->usesPatientPortalExperience()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Esta área é destinada ao titular no portal do paciente.'),
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('status', __('Os direitos LGPD no portal estão disponíveis na área do paciente. Acesse com a conta vinculada à sua ficha.'));
        }

        return $next($request);
    }
}
