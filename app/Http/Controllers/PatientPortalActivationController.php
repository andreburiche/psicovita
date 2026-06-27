<?php

namespace App\Http\Controllers;

use App\Services\PatientPortalProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PatientPortalActivationController extends Controller
{
    public function __construct(
        private readonly PatientPortalProvisioningService $portal,
    ) {}

    public function show(string $token): View|RedirectResponse
    {
        $invitation = $this->portal->findValidInvitation($token);

        if ($invitation === null) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Convite inválido ou expirado.')]);
        }

        return view('patient-portal.activate', [
            'invitation' => $invitation,
            'professional' => $invitation->invitedBy,
            'patient' => $invitation->patient,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->portal->findValidInvitation($token);

        if ($invitation === null) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Convite inválido ou expirado.')]);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms_accepted' => ['accepted'],
        ], [
            'terms_accepted.accepted' => __('Você deve aceitar os Termos de Uso e a Política de Privacidade.'),
        ]);

        $user = $this->portal->activate($invitation, $validated['password']);

        auth()->login($user);

        return redirect()
            ->route($user->defaultAppRouteName())
            ->with('status', __('Acesso ao portal activado com sucesso. Bem-vindo(a)!'));
    }
}
