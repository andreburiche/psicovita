<?php

namespace App\Services;

use App\Enums\VideoCallStatus;
use App\Enums\VideoRecordingStatus;
use App\Models\TherapySession;
use App\Models\TherapySessionVideoCall;
use App\Models\User;
use Illuminate\Support\Str;

class SessionVideoCallService
{
    public function __construct(
        private readonly SessionParticipantService $participantService,
    ) {}

    public function ensureForSession(TherapySession $session): TherapySessionVideoCall
    {
        $existing = $session->videoCall;
        if ($existing) {
            $this->participantService->syncForVideoCall($session, $existing);

            return $existing;
        }

        $prefix = (string) config('psiconecta.video_conference.room_prefix', 'psiconecta');
        $roomName = Str::lower($prefix.'-'.$session->id.'-'.Str::random(8));

        $videoCall = TherapySessionVideoCall::query()->create([
            'therapy_session_id' => $session->id,
            'room_name' => $roomName,
            'guest_token' => Str::random(48),
            'status' => VideoCallStatus::Pending,
            'recording_status' => VideoRecordingStatus::None,
        ]);

        $this->participantService->syncForVideoCall($session, $videoCall);

        return $videoCall;
    }

    public function markLive(TherapySessionVideoCall $videoCall): void
    {
        $videoCall->update([
            'status' => VideoCallStatus::Live,
            'started_at' => $videoCall->started_at ?? now(),
        ]);
    }

    public function markEnded(TherapySessionVideoCall $videoCall): void
    {
        $videoCall->update([
            'status' => VideoCallStatus::Ended,
            'ended_at' => now(),
        ]);
    }

    public function guestJoinUrl(TherapySessionVideoCall $videoCall): string
    {
        return route('session-video.guest', ['token' => $videoCall->guest_token]);
    }

    public function jitsiDomain(): string
    {
        return (string) config('psiconecta.video_conference.jitsi_domain', 'meet.jit.si');
    }

    public function displayNameFor(User $user): string
    {
        return $user->name ?: __('Profissional');
    }

    public function patientDisplayName(TherapySession $session): string
    {
        return $session->patient?->name ?: __('Paciente');
    }
}
