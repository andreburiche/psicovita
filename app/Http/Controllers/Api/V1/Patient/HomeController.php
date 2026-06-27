<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientApi\PaymentResource;
use App\Http\Resources\PatientApi\SessionResource;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientPortalSessionService;
use App\Services\PaymentService;
use App\Support\ContactHasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PatientPortalSessionService $portalSessions,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('therapist');
        $therapist = $user->therapist ?? $this->therapistFromPatientFicha($user);

        $pending = $this->payments->patientPendingSummary($user);
        $nextSession = $this->portalSessions->upcomingOnlineSessions($user, 1)->first();

        return response()->json([
            'data' => [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'therapist' => $therapist ? [
                    'id' => $therapist->id,
                    'name' => $therapist->name,
                    'email' => $therapist->email,
                    'professional_bio' => $therapist->professional_bio,
                    'avatar_url' => $therapist->avatarUrl(),
                ] : null,
                'pending_payments' => [
                    'count' => (int) ($pending['count'] ?? 0),
                    'total_cents' => (int) round((float) ($pending['total'] ?? 0) * 100),
                ],
                'next_session' => $nextSession
                    ? SessionResource::make($nextSession)
                    : null,
                'recent_payments' => PaymentResource::collection(
                    $this->payments->paginatePortalPayments($user, 3)->getCollection()
                ),
            ],
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

        return $professionalId ? User::query()->find((int) $professionalId) : null;
    }
}
