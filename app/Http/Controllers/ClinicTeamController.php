<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ClinicTeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClinicTeamController extends Controller
{
    public function __construct(
        private readonly ClinicTeamService $teams,
    ) {}

    public function storeInvite(Request $request): RedirectResponse
    {
        $owner = $request->user();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        try {
            $this->teams->invite($owner, $validated['email']);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('profile.edit')
                ->withErrors(['clinic_team' => $e->getMessage()]);
        }

        return redirect()
            ->route('profile.edit')
            ->with('status', __('Convite enviado para :email.', ['email' => $validated['email']]));
    }

    public function destroyMember(Request $request, User $member): RedirectResponse
    {
        try {
            $this->teams->removeMember($request->user(), $member);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('profile.edit')
                ->withErrors(['clinic_team' => $e->getMessage()]);
        }

        return redirect()
            ->route('profile.edit')
            ->with('status', __('Membro removido da equipa.'));
    }
}
