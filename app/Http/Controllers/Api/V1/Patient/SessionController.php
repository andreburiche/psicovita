<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Enums\TherapySessionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\PatientApi\SessionResource;
use App\Models\TherapySession;
use App\Services\PatientPortalSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(
        private readonly PatientPortalSessionService $portalSessions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sessions = $this->portalSessions->upcomingOnlineSessions(
            $request->user(),
            (int) $request->query('limit', 20)
        );

        return response()->json([
            'data' => SessionResource::collection($sessions),
        ]);
    }

    public function join(Request $request, TherapySession $therapySession): JsonResponse
    {
        abort_unless($therapySession->type === TherapySessionType::Online, 404);
        abort_unless($this->portalSessions->userCanAccessSession($request->user(), $therapySession), 403);

        $therapySession->load('professional', 'videoCall');

        if (! $this->portalSessions->canPatientJoinNow($therapySession)) {
            return response()->json([
                'message' => $this->portalSessions->joinStatusLabel($therapySession),
                'data' => SessionResource::make($therapySession),
            ], 409);
        }

        $guestToken = $therapySession->videoCall?->guest_token;
        abort_unless(filled($guestToken), 404);

        return response()->json([
            'data' => [
                'jitsi_domain' => config('psiconecta.video_conference.jitsi_domain', 'meet.jit.si'),
                'room_name' => $therapySession->videoCall?->room_name,
                'user_name' => $request->user()->name,
                'user_email' => $request->user()->email,
                'join_url' => route('session-video.guest', ['token' => $guestToken]),
                'session' => SessionResource::make($therapySession),
            ],
        ]);
    }
}
