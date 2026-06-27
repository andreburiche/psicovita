<?php

namespace App\Http\Controllers;

use App\Enums\SessionMode;
use App\Enums\SessionParticipantRole;
use App\Enums\TherapySessionStatus;
use App\Enums\VideoCallStatus;
use App\Enums\VideoRecordingStatus;
use App\Jobs\ProcessSessionVideoRecordingJob;
use App\Models\ClinicalRecord;
use App\Models\RecordAccessLog;
use App\Models\TherapySession;
use App\Models\TherapySessionVideoCall;
use App\Services\SessionParticipantService;
use App\Services\SessionVideoCallService;
use App\Services\SessionVideoProcessingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TherapySessionVideoCallController extends Controller
{
    public function __construct(
        private readonly SessionVideoCallService $videoCallService,
        private readonly SessionParticipantService $participantService,
        private readonly SessionVideoProcessingService $processingService,
    ) {}

    public function room(Request $request, TherapySession $therapySession): View|RedirectResponse
    {
        $this->authorize('view', $therapySession);

        if ($therapySession->status === TherapySessionStatus::Cancelled) {
            return redirect()
                ->route('therapy-sessions.show', $therapySession)
                ->withErrors(['video' => __('Sessão cancelada — não é possível iniciar videoconferência.')]);
        }

        $therapySession->load('patient', 'videoCall', 'participants');
        $videoCall = $this->videoCallService->ensureForSession($therapySession);
        $therapySession->load('participants');

        $observers = $this->participantService->observerParticipants($therapySession);
        $observer = $observers->first();
        $familyGuests = $this->participantService->guestParticipants($therapySession);
        $groupMembers = $therapySession->session_mode === SessionMode::Group
            ? $this->participantService->patientParticipants($therapySession)
            : collect();
        $isGroupSession = $therapySession->session_mode === SessionMode::Group;

        return view('session-video.room', [
            'session' => $therapySession,
            'videoCall' => $videoCall,
            'jitsiDomain' => $this->videoCallService->jitsiDomain(),
            'guestJoinUrl' => $this->videoCallService->guestJoinUrl($videoCall),
            'displayName' => $this->videoCallService->displayNameFor($request->user()),
            'participants' => $therapySession->participants,
            'observerJoinUrl' => $observer?->joinUrl(),
            'observers' => $observers,
            'familyGuests' => $familyGuests,
            'groupMembers' => $groupMembers,
            'isGroupSession' => $isGroupSession,
            'allRecordingConsentsGiven' => $this->participantService->allRecordingConsentsGiven($therapySession),
            'pendingRecordingConsents' => $this->participantService->pendingRecordingConsents($therapySession),
        ]);
    }

    public function guestJoin(string $token): View
    {
        $participant = $this->participantService->findByGuestToken($token);

        if ($participant) {
            return $this->renderGuestJoin($participant);
        }

        $videoCall = TherapySessionVideoCall::query()
            ->where('guest_token', $token)
            ->with('therapySession.patient', 'therapySession.professional')
            ->firstOrFail();

        $this->videoCallService->ensureForSession($videoCall->therapySession);
        $participant = $this->participantService->findByGuestToken($token);

        if ($participant) {
            return $this->renderGuestJoin($participant);
        }

        if ($videoCall->status === VideoCallStatus::Ended) {
            abort(410, __('Esta chamada já foi encerrada.'));
        }

        return view('session-video.guest', [
            'videoCall' => $videoCall,
            'session' => $videoCall->therapySession,
            'participant' => null,
            'jitsiDomain' => $this->videoCallService->jitsiDomain(),
            'displayName' => $this->videoCallService->patientDisplayName($videoCall->therapySession),
            'jitsiConfig' => [
                'displayName' => $this->videoCallService->patientDisplayName($videoCall->therapySession),
                'startAudioMuted' => false,
                'startVideoMuted' => false,
            ],
            'needsConsent' => true,
            'consentUrl' => route('session-video.consent', ['token' => $token]),
            'roleLabel' => SessionParticipantRole::Patient->label(),
            'isObserver' => false,
        ]);
    }

    public function guestConsent(Request $request, string $token): RedirectResponse
    {
        $participant = $this->participantService->findByGuestToken($token);

        if (! $participant) {
            $videoCall = TherapySessionVideoCall::query()
                ->where('guest_token', $token)
                ->with('therapySession')
                ->firstOrFail();

            $this->videoCallService->ensureForSession($videoCall->therapySession);
            $participant = $this->participantService->findByGuestToken($token);
        }

        abort_unless($participant, 404);

        if ($participant->therapySession->videoCall?->status === VideoCallStatus::Ended) {
            abort(410, __('Esta chamada já foi encerrada.'));
        }

        $validated = $request->validate([
            'join_consent' => ['accepted'],
            'recording_consent' => ['nullable', 'boolean'],
        ]);

        $this->participantService->recordJoinConsent(
            $participant,
            (bool) ($validated['recording_consent'] ?? false),
            $request->ip(),
        );

        return redirect()
            ->route('session-video.guest', ['token' => $token])
            ->with('status', __('Consentimento registado. A entrar na sala…'));
    }

    public function start(Request $request, TherapySession $therapySession): RedirectResponse
    {
        $this->authorize('view', $therapySession);

        $videoCall = $this->videoCallService->ensureForSession($therapySession);
        $this->videoCallService->markLive($videoCall);

        return back()->with('status', __('Sala de vídeo iniciada.'));
    }

    public function finish(Request $request, TherapySession $therapySession): RedirectResponse
    {
        $this->authorize('view', $therapySession);

        $validated = $request->validate([
            'recording' => ['required', 'file', 'max:512000', 'mimes:webm,mp3,wav,m4a,ogg,mp4,mpeg'],
            'approach' => ['required', Rule::in([
                'freudiana', 'lacaniana', 'jungiana', 'winnicottiana', 'humanista', 'tcc', 'sistemica',
            ])],
            'lgpd_recording_consent' => ['accepted'],
        ]);

        $therapySession->load('videoCall', 'participants');
        $videoCall = $this->videoCallService->ensureForSession($therapySession);

        $this->participantService->recordHostRecordingConsent($therapySession);

        if (! $this->participantService->allRecordingConsentsGiven($therapySession)) {
            throw ValidationException::withMessages([
                'recording' => __('Aguardando consentimento de gravação de todos os participantes: :names.', [
                    'names' => collect($this->participantService->pendingRecordingConsents($therapySession))
                        ->pluck('name')
                        ->join(', '),
                ]),
            ]);
        }

        $disk = (string) config('psiconecta.video_conference.recording_disk', 'local');
        $path = $request->file('recording')->store(
            'session-recordings/'.$therapySession->id,
            $disk,
        );

        $this->videoCallService->markEnded($videoCall);

        $videoCall->update([
            'approach' => $validated['approach'],
            'recording_disk' => $disk,
            'recording_path' => $path,
            'recording_size_bytes' => $request->file('recording')->getSize(),
            'recording_status' => VideoRecordingStatus::Uploaded,
            'recording_consent_at' => now(),
            'recording_consent_ip' => $request->ip(),
        ]);

        ProcessSessionVideoRecordingJob::dispatch($videoCall->id);

        return redirect()
            ->route('therapy-sessions.video.review', $therapySession)
            ->with('status', __('Sessão encerrada. A IA está processando a gravação — a página atualiza automaticamente.'));
    }

    public function review(Request $request, TherapySession $therapySession): View
    {
        $this->authorize('view', $therapySession);

        $therapySession->load('patient', 'videoCall');

        $videoCall = $therapySession->videoCall;
        abort_unless($videoCall, 404);

        return view('session-video.review', [
            'session' => $therapySession,
            'videoCall' => $videoCall,
            'guestJoinUrl' => $this->videoCallService->guestJoinUrl($videoCall),
        ]);
    }

    public function regenerateDevolutiva(Request $request, TherapySession $therapySession): RedirectResponse
    {
        $this->authorize('view', $therapySession);

        $validated = $request->validate([
            'approach' => ['required', Rule::in([
                'freudiana', 'lacaniana', 'jungiana', 'winnicottiana', 'humanista', 'tcc', 'sistemica',
            ])],
        ]);

        $therapySession->load('videoCall');
        $videoCall = $therapySession->videoCall;
        abort_unless($videoCall, 404);

        try {
            $this->processingService->regenerateDevolutiva($videoCall, $validated['approach']);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['approach' => \App\Services\AiAssistantService::userFacingErrorMessage($e)]);
        }

        return back()->with('status', __('Devolutiva regenerada — revisão obrigatória.'));
    }

    public function saveToRecord(Request $request, TherapySession $therapySession): RedirectResponse
    {
        $this->authorize('view', $therapySession);

        $validated = $request->validate([
            'content_type' => ['required', Rule::in(['transcription', 'clinical_summary', 'devolutiva', 'full'])],
        ]);

        $therapySession->load('patient', 'videoCall');
        $videoCall = $therapySession->videoCall;
        abort_unless($videoCall?->isReadyForReview(), 404);

        $content = match ($validated['content_type']) {
            'transcription' => $videoCall->transcription_text,
            'clinical_summary' => $videoCall->clinical_summary_text,
            'devolutiva' => $videoCall->devolutiva_patient_text,
            'full' => $this->composeFullRecord($videoCall),
        };

        if (! $content) {
            return back()->withErrors(['save' => __('Conteúdo indisponível para arquivar.')]);
        }

        $header = '— '.__('Sessão por vídeo').' · '.$therapySession->session_date->format('d/m/Y')." —\n\n";
        $header .= __('Conteúdo gerado com apoio de IA — revisão profissional obrigatória.')."\n\n";

        $record = ClinicalRecord::query()->create([
            'patient_id' => $therapySession->patient_id,
            'professional_id' => $therapySession->professional_id,
            'content' => $header.$content,
        ]);

        RecordAccessLog::query()->create([
            'user_id' => $request->user()->id,
            'clinical_record_id' => $record->id,
            'action' => RecordAccessLog::ACTION_CREATED,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('clinical-records.show', $record)
            ->with('status', __('Conteúdo arquivado no prontuário.'));
    }

    private function renderGuestJoin(\App\Models\SessionParticipant $participant): View
    {
        $session = $participant->therapySession;

        if ($session->videoCall?->status === VideoCallStatus::Ended) {
            abort(410, __('Esta chamada já foi encerrada.'));
        }

        $videoCall = $this->videoCallService->ensureForSession($session);

        $jitsiConfig = $this->participantService->jitsiConfigFor($participant);
        $isObserver = $participant->role === SessionParticipantRole::Observer;
        $isFamilyGuest = $participant->role === SessionParticipantRole::Guest;
        $isGroupMember = $participant->role === SessionParticipantRole::Patient
            && $session->session_mode === SessionMode::Group;

        return view('session-video.guest', [
            'videoCall' => $videoCall,
            'session' => $session,
            'participant' => $participant,
            'jitsiDomain' => $this->videoCallService->jitsiDomain(),
            'displayName' => $jitsiConfig['displayName'],
            'jitsiConfig' => $jitsiConfig,
            'needsConsent' => $participant->join_consent_at === null,
            'consentUrl' => route('session-video.consent', ['token' => $participant->guest_token]),
            'roleLabel' => $participant->role->label(),
            'isObserver' => $isObserver,
            'isFamilyGuest' => $isFamilyGuest,
            'isGroupMember' => $isGroupMember,
        ]);
    }

    private function composeFullRecord(TherapySessionVideoCall $videoCall): string
    {
        $parts = array_filter([
            $videoCall->transcription_text ? __('Transcrição').":\n".$videoCall->transcription_text : null,
            $videoCall->clinical_summary_text ? __('Resumo clínico').":\n".$videoCall->clinical_summary_text : null,
            $videoCall->devolutiva_patient_text ? __('Devolutiva ao paciente').":\n".$videoCall->devolutiva_patient_text : null,
        ]);

        return implode("\n\n---\n\n", $parts);
    }
}
