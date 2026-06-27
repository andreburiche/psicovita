<?php

namespace App\Http\Controllers;

use App\Services\ClinicTeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClinicInvitationController extends Controller
{
    public function __construct(
        private readonly ClinicTeamService $teams,
    ) {}

    public function show(string $token): View|RedirectResponse
    {
        $invitation = $this->teams->findValidInvitation($token);

        if ($invitation === null) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Convite inválido ou expirado.')]);
        }

        return view('clinic.invitation-accept', [
            'invitation' => $invitation,
            'owner' => $invitation->owner,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->teams->findValidInvitation($token);

        if ($invitation === null) {
            return redirect()
                ->route('dashboard')
                ->withErrors(['clinic_team' => __('Convite inválido ou expirado.')]);
        }

        try {
            $this->teams->acceptInvitation($invitation, $request->user());
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('clinic.invitations.show', $token)
                ->withErrors(['clinic_team' => $e->getMessage()]);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', __('Entrou na equipa de :name.', ['name' => $invitation->owner?->name ?? __('clínica')]));
    }
}
