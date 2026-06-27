<?php

namespace App\Http\Controllers;

use App\Enums\TherapySessionType;
use App\Models\TherapySession;
use App\Services\PatientPortalSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientPortalSessionController extends Controller
{
    public function __construct(
        private readonly PatientPortalSessionService $portalSessions,
    ) {}

    public function index(Request $request): View
    {
        $sessions = $this->portalSessions->upcomingOnlineSessions($request->user());

        return view('patient.sessions.index', [
            'sessions' => $sessions,
            'portalSessions' => $this->portalSessions,
        ]);
    }

    public function join(Request $request, TherapySession $therapySession): RedirectResponse|View
    {
        abort_unless($therapySession->type === TherapySessionType::Online, 404);
        abort_unless($this->portalSessions->userCanAccessSession($request->user(), $therapySession), 403);

        $therapySession->load('patient', 'professional', 'videoCall');

        if (! $this->portalSessions->canPatientJoinNow($therapySession)) {
            return redirect()
                ->route('patient.sessions.index')
                ->withErrors(['join' => $this->portalSessions->joinStatusLabel($therapySession)]);
        }

        $videoCall = $therapySession->videoCall;
        abort_unless($videoCall, 404);

        return redirect()->route('session-video.guest', ['token' => $videoCall->guest_token]);
    }
}
