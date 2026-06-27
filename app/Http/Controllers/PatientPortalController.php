<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Services\PatientPortalSessionService;
use App\Services\PaymentService;
use App\Support\ContactHasher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientPortalController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PatientPortalSessionService $portalSessions,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing('therapist');

        $therapist = $user->therapist;
        if ($therapist === null && $user->usesPatientPortalExperience()) {
            $therapist = $this->therapistFromPatientFicha($user);
        }

        return view('patient.home', [
            'user' => $user,
            'therapist' => $therapist,
            'pendingPayments' => $this->payments->patientPendingSummary($user),
            'upcomingVideoSessions' => $this->portalSessions->upcomingOnlineSessions($user, 3),
            'portalSessions' => $this->portalSessions,
            'notifications' => $user->notifications()->latest()->limit(20)->get(),
        ]);
    }

    private function therapistFromPatientFicha(User $user): ?User
    {
        $email = $user->normalizedEmail();
        if ($email === '') {
            return null;
        }

        $professionalId = Patient::query()
            ->where('email_hash', ContactHasher::emailHash($email))
            ->where('professional_id', '<>', $user->id)
            ->orderBy('id')
            ->value('professional_id');

        if ($professionalId === null) {
            return null;
        }

        return User::query()->find((int) $professionalId);
    }
}
