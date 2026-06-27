<?php

namespace App\Http\Middleware;

use App\Services\ClinicTeamService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfessional
{
    public function __construct(
        private readonly ClinicTeamService $clinicTeams,
    ) {}

    /**
     * Área clínica do PsiConecta: somente perfil profissional (tenant = próprio usuário).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if ($user->isClinicTeamMember() && ! $this->clinicTeams->membershipIsActive($user)) {
            $user->update(['clinic_owner_id' => null]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('A ligação à equipa foi removida porque o plano do consultório já não inclui multi-utilizador.'),
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('status', __('A ligação à equipa foi removida porque o plano do consultório já não inclui multi-utilizador.'));
        }

        if ($user->usesPatientPortalExperience()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Este acesso é apenas para profissionais. Utilize a área do paciente.'),
                ], 403);
            }

            return redirect()
                ->route('patient.home')
                ->with('status', __('A área clínica é só para o seu profissional. Está no espaço do paciente.'));
        }

        if (! $user->isProfessional() && ! $user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Acesso restrito a profissionais.',
                ], 403);
            }

            if ($user->isPatient()) {
                return redirect()
                    ->route('patient.home')
                    ->with('status', __('A área clínica é exclusiva do seu profissional. Foi redirecionado para o seu espaço como paciente.'));
            }

            abort(403, 'Acesso restrito a profissionais.');
        }

        return $next($request);
    }
}
