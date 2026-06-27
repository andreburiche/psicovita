<?php

namespace App\Services;

use App\Enums\SessionMode;
use App\Enums\SessionParticipantRole;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\SessionParticipant;
use App\Models\TherapySession;
use App\Models\TherapySessionVideoCall;
use App\Models\User;
use App\Notifications\SessionFamilyGuestInviteNotification;
use App\Notifications\SessionGroupMemberInviteNotification;
use App\Notifications\SessionObserverInviteNotification;
use App\Support\ContactHasher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class SessionParticipantService
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function listPracticeProfessionals(User $professional): Collection
    {
        $practiceId = $professional->clinicalPracticeId();

        return User::query()
            ->where('role', UserRole::Professional)
            ->where('id', '!=', $professional->id)
            ->where(function ($query) use ($practiceId): void {
                $query->where('id', $practiceId)
                    ->orWhere('clinic_owner_id', $practiceId);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function professionalBelongsToPractice(User $professional, int $userId): bool
    {
        return $this->listPracticeProfessionals($professional)->contains('id', $userId);
    }

    public function syncForVideoCall(TherapySession $session, TherapySessionVideoCall $videoCall): void
    {
        $session->loadMissing(['patient', 'professional', 'participants']);

        $this->upsertHost($session);

        if ($session->session_mode === SessionMode::Group) {
            $this->syncGroupPatientParticipants($session, $videoCall);

            return;
        }

        if (! $session->patient_id) {
            return;
        }

        $patientUser = $session->patient
            ? $this->conversationService->resolvePortalUserForPatient($session->patient, $session->professional)
            : null;

        $this->upsertPatientParticipant($session, $session->patient_id, [
            'user_id' => $patientUser?->id,
            'display_name' => $session->patient?->name ?: __('Paciente'),
            'guest_token' => $videoCall->guest_token,
        ]);
    }

    /**
     * @param  list<int>  $patientIds
     * @return list<SessionParticipant>
     */
    public function ensureGroupMembersFromRequest(TherapySession $session, array $patientIds): array
    {
        if ($session->session_mode !== SessionMode::Group) {
            return [];
        }

        $created = [];
        foreach ($patientIds as $patientId) {
            $created[] = $this->upsertGroupMember($session, (int) $patientId, sendInvite: true);
        }

        return $created;
    }

    public function patientParticipants(TherapySession $session): Collection
    {
        return $session->participants()
            ->where('role', SessionParticipantRole::Patient)
            ->orderBy('id')
            ->get();
    }

    public function upsertGroupMember(
        TherapySession $session,
        int $patientId,
        bool $sendInvite = false,
    ): SessionParticipant {
        $patient = Patient::query()
            ->where('professional_id', $session->professional_id)
            ->findOrFail($patientId);

        $portalUser = $this->conversationService->resolvePortalUserForPatient($patient, $session->professional);
        $email = $patient->email ? strtolower(trim((string) $patient->email)) : null;

        $existingToken = SessionParticipant::query()
            ->where('therapy_session_id', $session->id)
            ->where('patient_id', $patientId)
            ->value('guest_token');

        /** @var SessionParticipant $participant */
        $participant = SessionParticipant::query()->updateOrCreate(
            [
                'therapy_session_id' => $session->id,
                'patient_id' => $patientId,
            ],
            [
                'role' => SessionParticipantRole::Patient,
                'user_id' => $portalUser?->id,
                'display_name' => $patient->name,
                'email' => $email,
                'guest_token' => $existingToken ?: Str::random(48),
            ],
        );

        if ($sendInvite && filled($participant->email)) {
            $this->sendGroupMemberInvite($participant);
        }

        return $participant;
    }

    public function sendGroupMemberInvite(SessionParticipant $participant): void
    {
        if ($participant->role !== SessionParticipantRole::Patient || blank($participant->email)) {
            return;
        }

        $participant->loadMissing('therapySession');
        if ($participant->therapySession?->session_mode !== SessionMode::Group) {
            return;
        }

        Notification::route('mail', $participant->email)
            ->notify(new SessionGroupMemberInviteNotification($participant));
    }

    private function syncGroupPatientParticipants(TherapySession $session, TherapySessionVideoCall $videoCall): void
    {
        $members = $this->patientParticipants($session);

        if ($members->isEmpty() && $session->patient_id) {
            $this->upsertGroupMember($session, (int) $session->patient_id);
            $members = $this->patientParticipants($session);
        }

        foreach ($members as $member) {
            $token = (int) $member->patient_id === (int) $session->patient_id
                ? $videoCall->guest_token
                : ($member->guest_token ?: Str::random(48));

            if ($member->guest_token !== $token) {
                $member->update(['guest_token' => $token]);
            }
        }
    }

    private function upsertHost(TherapySession $session): SessionParticipant
    {
        return $this->upsertParticipant($session, [
            'role' => SessionParticipantRole::Host,
            'user_id' => $session->professional_id,
            'display_name' => $session->professional?->name ?: __('Profissional'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertPatientParticipant(TherapySession $session, ?int $patientId, array $attributes): SessionParticipant
    {
        if ($patientId === null) {
            return $this->upsertParticipant($session, array_merge($attributes, [
                'role' => SessionParticipantRole::Patient,
            ]));
        }

        /** @var SessionParticipant $participant */
        $participant = SessionParticipant::query()->updateOrCreate(
            [
                'therapy_session_id' => $session->id,
                'patient_id' => $patientId,
            ],
            array_merge($attributes, [
                'role' => SessionParticipantRole::Patient,
            ]),
        );

        return $participant;
    }

    public function addFamilyGuestFromPatient(
        TherapySession $session,
        int $patientId,
        bool $sendInvite = true,
    ): ?SessionParticipant {
        if ((int) $patientId === (int) $session->patient_id) {
            return null;
        }

        $patient = Patient::query()
            ->where('professional_id', $session->professional_id)
            ->find($patientId);

        if (! $patient) {
            return null;
        }

        $portalUser = $this->conversationService->resolvePortalUserForPatient($patient, $session->professional);
        $email = $patient->email ? strtolower(trim((string) $patient->email)) : null;

        /** @var SessionParticipant $participant */
        $participant = SessionParticipant::query()->updateOrCreate(
            [
                'therapy_session_id' => $session->id,
                'patient_id' => $patientId,
            ],
            [
                'role' => SessionParticipantRole::Guest,
                'user_id' => $portalUser?->id,
                'display_name' => $patient->name,
                'email' => $email,
                'guest_token' => SessionParticipant::query()
                    ->where('therapy_session_id', $session->id)
                    ->where('patient_id', $patientId)
                    ->value('guest_token') ?: Str::random(48),
            ],
        );

        if ($sendInvite && filled($participant->email)) {
            $this->sendFamilyGuestInvite($participant);
        }

        return $participant;
    }

    /**
     * @param  list<int>  $patientIds
     * @param  list<array{name: string, email: string}>  $externalGuests
     * @return list<SessionParticipant>
     */
    public function ensureFamilyParticipantsFromRequest(
        TherapySession $session,
        array $patientIds,
        array $externalGuests,
    ): array {
        if ($session->session_mode !== SessionMode::Family) {
            return [];
        }

        $created = [];

        foreach ($patientIds as $patientId) {
            $participant = $this->addFamilyGuestFromPatient($session, (int) $patientId);
            if ($participant) {
                $created[] = $participant;
            }
        }

        return array_merge($created, $this->ensureFamilyGuestsFromRequest($session, $externalGuests));
    }

    public function addFamilyGuest(
        TherapySession $session,
        string $displayName,
        string $email,
        bool $sendInvite = true,
    ): SessionParticipant {
        $email = strtolower(trim($email));
        $patient = $this->resolvePatientForGuestEmail($session, $email);
        $portalUser = $patient
            ? $this->conversationService->resolvePortalUserForPatient($patient, $session->professional)
            : null;

        $participant = SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Guest,
            'patient_id' => $patient?->id,
            'user_id' => $portalUser?->id,
            'display_name' => trim($displayName),
            'email' => $email,
            'guest_token' => Str::random(48),
        ]);

        if ($sendInvite) {
            $this->sendFamilyGuestInvite($participant);
        }

        return $participant;
    }

    public function sendFamilyGuestInvite(SessionParticipant $participant): void
    {
        if ($participant->role !== SessionParticipantRole::Guest || blank($participant->email)) {
            return;
        }

        Notification::route('mail', $participant->email)
            ->notify(new SessionFamilyGuestInviteNotification($participant));
    }

    /**
     * @param  list<array{name: string, email: string}>  $guests
     * @return list<SessionParticipant>
     */
    public function ensureFamilyGuestsFromRequest(TherapySession $session, array $guests): array
    {
        if ($session->session_mode !== SessionMode::Family) {
            return [];
        }

        $created = [];
        $existingEmails = $this->guestParticipants($session)
            ->pluck('email')
            ->filter()
            ->map(fn (?string $email) => strtolower(trim((string) $email)))
            ->all();

        foreach ($guests as $guest) {
            $name = trim((string) ($guest['name'] ?? ''));
            $email = strtolower(trim((string) ($guest['email'] ?? '')));

            if ($name === '' || $email === '') {
                continue;
            }

            if (in_array($email, $existingEmails, true)) {
                continue;
            }

            $created[] = $this->addFamilyGuest($session, $name, $email);
            $existingEmails[] = $email;
        }

        return $created;
    }

    public function guestParticipants(TherapySession $session): Collection
    {
        return $session->participants()
            ->where('role', SessionParticipantRole::Guest)
            ->orderBy('id')
            ->get();
    }

    public function resolvePatientForGuestEmail(TherapySession $session, string $email): ?Patient
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return Patient::query()
            ->where('professional_id', $session->professional_id)
            ->where('email_hash', ContactHasher::emailHash($email))
            ->where('id', '!=', $session->patient_id)
            ->first();
    }

    public function sendParticipantInvite(SessionParticipant $participant): void
    {
        $participant->loadMissing('therapySession');

        match ($participant->role) {
            SessionParticipantRole::Observer => $this->sendObserverInvite($participant),
            SessionParticipantRole::Guest => $this->sendFamilyGuestInvite($participant),
            SessionParticipantRole::Patient => $this->sendGroupMemberInvite($participant),
            default => null,
        };
    }

    public function addObserverFromProfessional(
        TherapySession $session,
        User $observerUser,
        bool $sendInvite = true,
    ): SessionParticipant {
        $participant = SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Observer,
            'user_id' => $observerUser->id,
            'display_name' => $observerUser->name ?: __('Profissional'),
            'email' => $observerUser->normalizedEmail() ?: null,
            'guest_token' => Str::random(48),
        ]);

        if ($sendInvite && filled($participant->email)) {
            $this->sendObserverInvite($participant);
        }

        return $participant;
    }

    public function addObserver(
        TherapySession $session,
        string $displayName,
        string $email,
        bool $sendInvite = true,
    ): SessionParticipant {
        $participant = SessionParticipant::query()->create([
            'therapy_session_id' => $session->id,
            'role' => SessionParticipantRole::Observer,
            'display_name' => trim($displayName),
            'email' => strtolower(trim($email)),
            'guest_token' => Str::random(48),
        ]);

        if ($sendInvite) {
            $this->sendObserverInvite($participant);
        }

        return $participant;
    }

    public function sendObserverInvite(SessionParticipant $participant): void
    {
        if ($participant->role !== SessionParticipantRole::Observer || blank($participant->email)) {
            return;
        }

        Notification::route('mail', $participant->email)
            ->notify(new SessionObserverInviteNotification($participant));
    }

    public function findByGuestToken(string $token): ?SessionParticipant
    {
        return SessionParticipant::query()
            ->where('guest_token', $token)
            ->with(['therapySession.patient', 'therapySession.professional', 'therapySession.videoCall'])
            ->first();
    }

    public function recordJoinConsent(SessionParticipant $participant, bool $recordingConsent, ?string $ip = null): void
    {
        $updates = [
            'join_consent_at' => now(),
            'joined_at' => now(),
        ];

        if ($recordingConsent) {
            $updates['recording_consent_at'] = now();
            $updates['recording_consent_ip'] = $ip;
        }

        $participant->update($updates);
    }

    public function recordHostRecordingConsent(TherapySession $session): void
    {
        $host = $this->hostParticipant($session);
        if ($host) {
            $host->update([
                'recording_consent_at' => now(),
            ]);
        }
    }

    public function allRecordingConsentsGiven(TherapySession $session): bool
    {
        $participants = $session->participants()->get();

        $required = $participants->filter(
            fn (SessionParticipant $p) => $this->requiresRecordingConsent($p)
        );

        if ($required->isEmpty()) {
            return true;
        }

        return $required->every(fn (SessionParticipant $p) => $p->hasRecordingConsent());
    }

    public function pendingRecordingConsents(TherapySession $session): array
    {
        return $session->participants()
            ->get()
            ->filter(fn (SessionParticipant $p) => $this->requiresRecordingConsent($p) && ! $p->hasRecordingConsent())
            ->map(fn (SessionParticipant $p) => [
                'name' => $p->display_name,
                'role' => $p->role->label(),
            ])
            ->values()
            ->all();
    }

    private function requiresRecordingConsent(SessionParticipant $participant): bool
    {
        if (! $participant->role->mustConsentForRecording()) {
            return false;
        }

        if ($participant->role === SessionParticipantRole::Host) {
            return true;
        }

        return $participant->join_consent_at !== null;
    }

    public function jitsiConfigFor(SessionParticipant $participant): array
    {
        return [
            'displayName' => $participant->display_name,
            'startAudioMuted' => $participant->role->joinsMuted(),
            'startVideoMuted' => $participant->role->joinsMuted(),
            'disableAudioLevels' => $participant->role->joinsMuted(),
        ];
    }

    public function jitsiConfigForUser(User $user, SessionParticipantRole $role): array
    {
        return [
            'displayName' => $user->name ?: __('Profissional'),
            'startAudioMuted' => $role->joinsMuted(),
            'startVideoMuted' => $role->joinsMuted(),
            'disableAudioLevels' => $role->joinsMuted(),
        ];
    }

    public function hostParticipant(TherapySession $session): ?SessionParticipant
    {
        return $session->participants()
            ->where('role', SessionParticipantRole::Host)
            ->first();
    }

    public function observerParticipants(TherapySession $session): Collection
    {
        return $session->participants()
            ->where('role', SessionParticipantRole::Observer)
            ->orderBy('id')
            ->get();
    }

    public function billableParticipants(TherapySession $session): Collection
    {
        return $session->participants()
            ->whereIn('role', [
                SessionParticipantRole::Observer,
                SessionParticipantRole::Guest,
                SessionParticipantRole::Patient,
            ])
            ->orderBy('id')
            ->get();
    }

    public function participantBillingLabel(SessionParticipant $participant): string
    {
        if ($participant->role === SessionParticipantRole::Observer && $participant->user_id) {
            return __('Profissional (observador)');
        }

        if ($participant->role === SessionParticipantRole::Observer) {
            return __('Externo (escuta / supervisão)');
        }

        return $participant->role->label();
    }

    public function observerParticipant(TherapySession $session): ?SessionParticipant
    {
        return $this->observerParticipants($session)->first();
    }

    /**
     * @param  list<array{source: string, professional_id?: int|null, name?: string|null, email?: string|null}>  $items
     * @return list<SessionParticipant>
     */
    public function ensureObserversFromRequest(TherapySession $session, array $items, User $host): array
    {
        if ($session->session_mode !== SessionMode::WithObserver) {
            return [];
        }

        $created = [];

        foreach ($items as $item) {
            $source = (string) ($item['source'] ?? 'external');

            if ($source === 'professional' && ! empty($item['professional_id'])) {
                $professionalId = (int) $item['professional_id'];
                if (! $this->professionalBelongsToPractice($host, $professionalId)) {
                    continue;
                }

                if ($session->participants()->where('role', SessionParticipantRole::Observer)->where('user_id', $professionalId)->exists()) {
                    continue;
                }

                $observerUser = User::query()->find($professionalId);
                if ($observerUser) {
                    $created[] = $this->addObserverFromProfessional($session, $observerUser);
                }

                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));
            $email = strtolower(trim((string) ($item['email'] ?? '')));

            if ($name === '' || $email === '') {
                continue;
            }

            if ($session->participants()->where('role', SessionParticipantRole::Observer)->where('email', $email)->exists()) {
                continue;
            }

            $created[] = $this->addObserver($session, $name, $email);
        }

        return $created;
    }

    public function ensureObserverFromRequest(
        TherapySession $session,
        ?string $source,
        ?int $professionalId,
        ?string $name,
        ?string $email,
        User $host,
    ): ?SessionParticipant {
        if ($session->session_mode !== SessionMode::WithObserver) {
            return null;
        }

        $existing = $this->observerParticipant($session);
        if ($existing) {
            return $existing;
        }

        if ($source === 'professional' && $professionalId) {
            if (! $this->professionalBelongsToPractice($host, $professionalId)) {
                return null;
            }

            $observerUser = User::query()->find($professionalId);
            if (! $observerUser) {
                return null;
            }

            return $this->addObserverFromProfessional($session, $observerUser);
        }

        if (blank($name) || blank($email)) {
            return null;
        }

        return $this->addObserver($session, $name, $email);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertParticipant(TherapySession $session, array $attributes): SessionParticipant
    {
        $role = $attributes['role'];
        unset($attributes['role']);

        /** @var SessionParticipant $participant */
        $participant = SessionParticipant::query()->updateOrCreate(
            [
                'therapy_session_id' => $session->id,
                'role' => $role,
            ],
            $attributes,
        );

        return $participant;
    }
}
