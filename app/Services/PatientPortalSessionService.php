<?php

namespace App\Services;

use App\Enums\TherapySessionStatus;
use App\Enums\TherapySessionType;
use App\Enums\VideoCallStatus;
use App\Models\Patient;
use App\Models\TherapySession;
use App\Models\User;
use App\Support\ContactHasher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class PatientPortalSessionService
{
    /**
     * @return Collection<int, Patient>
     */
    public function patientsForUser(User $user): Collection
    {
        $email = $user->normalizedEmail();
        if ($email === '') {
            return new Collection;
        }

        return Patient::query()
            ->where('email_hash', ContactHasher::emailHash($email))
            ->with('professional')
            ->orderBy('name')
            ->get();
    }

    public function userCanAccessSession(User $user, TherapySession $session): bool
    {
        return $this->patientsForUser($user)->contains('id', $session->patient_id);
    }

    /**
     * @return Collection<int, TherapySession>
     */
    public function upcomingOnlineSessions(User $user, int $limit = 20): Collection
    {
        $patientIds = $this->patientsForUser($user)->pluck('id');
        if ($patientIds->isEmpty()) {
            return new Collection;
        }

        $fromDate = now()->subDay()->toDateString();

        return TherapySession::query()
            ->whereIn('patient_id', $patientIds)
            ->where('type', TherapySessionType::Online)
            ->whereIn('status', [TherapySessionStatus::Scheduled, TherapySessionStatus::Completed])
            ->where('session_date', '>=', $fromDate)
            ->with(['patient', 'professional', 'videoCall'])
            ->orderBy('session_date')
            ->orderBy('session_time')
            ->limit($limit)
            ->get()
            ->filter(fn (TherapySession $session) => $this->isJoinRelevant($session))
            ->values();
    }

    public function isJoinRelevant(TherapySession $session): bool
    {
        if ($session->status === TherapySessionStatus::Cancelled) {
            return false;
        }

        if ($session->videoCall?->status === VideoCallStatus::Live) {
            return true;
        }

        if ($session->session_date->lt(now()->subDays(2))) {
            return false;
        }

        return $session->status === TherapySessionStatus::Scheduled
            || $session->videoCall?->status === VideoCallStatus::Pending;
    }

    public function canPatientJoinNow(TherapySession $session): bool
    {
        if ($session->status === TherapySessionStatus::Cancelled) {
            return false;
        }

        $videoCall = $session->videoCall;
        if ($videoCall === null) {
            return false;
        }

        if ($videoCall->status === VideoCallStatus::Ended) {
            return false;
        }

        if ($videoCall->status === VideoCallStatus::Live) {
            return true;
        }

        return $this->isWithinJoinWindow($session);
    }

    public function isWithinJoinWindow(TherapySession $session): bool
    {
        $moment = $this->sessionMoment($session);

        return $moment->between(now()->subMinutes(30), now()->addHours(2));
    }

    public function sessionMoment(TherapySession $session): Carbon
    {
        $moment = $session->session_date->copy()->startOfDay();
        $time = is_string($session->session_time)
            ? substr($session->session_time, 0, 5)
            : $session->session_time->format('H:i');

        try {
            [$hour, $minute] = array_map('intval', explode(':', $time));
            $moment->setTime($hour, $minute);
        } catch (\Throwable) {
            // mantém meia-noite
        }

        return $moment;
    }

    public function joinStatusLabel(TherapySession $session): string
    {
        if ($session->videoCall?->status === VideoCallStatus::Live) {
            return __('Sala aberta — entre agora');
        }

        if ($this->canPatientJoinNow($session)) {
            return __('Entrar na consulta');
        }

        if ($session->videoCall === null) {
            return __('Aguardando o profissional abrir a sala');
        }

        if ($session->videoCall->status === VideoCallStatus::Ended) {
            return __('Consulta encerrada');
        }

        $moment = $this->sessionMoment($session);

        return __('Disponível perto de :datetime', [
            'datetime' => $moment->translatedFormat('d/m/Y H:i'),
        ]);
    }
}
