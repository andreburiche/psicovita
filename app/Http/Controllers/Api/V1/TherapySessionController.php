<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TherapySessionStatus;
use App\Enums\TherapySessionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\TherapySessionResource;
use App\Models\TherapySession;
use App\Services\ScheduleConflictService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TherapySessionController extends Controller
{
    public function __construct(
        private readonly ScheduleConflictService $scheduleConflict
    ) {
        $this->authorizeResource(TherapySession::class, 'therapy_session');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TherapySession::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->with('patient')
            ->orderByDesc('session_date')
            ->orderByDesc('session_time');

        if ($request->filled('from_date')) {
            $query->whereDate('session_date', '>=', $request->date('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('session_date', '<=', $request->date('to_date'));
        }

        return TherapySessionResource::collection(
            $query->paginate((int) $request->query('per_page', 20))->withQueryString()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
            'session_date' => ['required', 'date'],
            'session_time' => ['required', 'date_format:H:i'],
            'status' => ['required', Rule::enum(TherapySessionStatus::class)],
            'type' => ['required', Rule::enum(TherapySessionType::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $status = $validated['status'] instanceof TherapySessionStatus
            ? $validated['status']
            : TherapySessionStatus::from((string) $validated['status']);

        if ($this->scheduleConflict->hasConflict(
            $request->user()->clinicalPracticeId(),
            (string) $validated['session_date'],
            (string) $validated['session_time'],
            null,
            $status,
        )) {
            throw ValidationException::withMessages([
                'session_time' => [__('Horário indisponível: conflito com outra sessão ou bloqueio da agenda.')],
            ]);
        }

        $validated['professional_id'] = $request->user()->clinicalPracticeId();

        $session = TherapySession::query()->create($validated)->load('patient');

        return TherapySessionResource::make($session)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(TherapySession $therapySession): TherapySessionResource
    {
        $therapySession->load('patient');

        return new TherapySessionResource($therapySession);
    }

    public function update(Request $request, TherapySession $therapySession): TherapySessionResource
    {
        $validated = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
            'session_date' => ['required', 'date'],
            'session_time' => ['required', 'date_format:H:i'],
            'status' => ['required', Rule::enum(TherapySessionStatus::class)],
            'type' => ['required', Rule::enum(TherapySessionType::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $status = $validated['status'] instanceof TherapySessionStatus
            ? $validated['status']
            : TherapySessionStatus::from((string) $validated['status']);

        if ($this->scheduleConflict->hasConflict(
            $request->user()->clinicalPracticeId(),
            (string) $validated['session_date'],
            (string) $validated['session_time'],
            $therapySession->id,
            $status,
        )) {
            throw ValidationException::withMessages([
                'session_time' => [__('Horário indisponível: conflito com outra sessão ou bloqueio da agenda.')],
            ]);
        }

        $therapySession->update($validated);

        return new TherapySessionResource($therapySession->fresh()->load('patient'));
    }

    public function destroy(TherapySession $therapySession): Response
    {
        $therapySession->delete();

        return response()->noContent();
    }
}
