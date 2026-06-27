<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Enums\DataSubjectRequestType;
use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Services\DataSubjectRequestService;
use App\Support\PatientAccountResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class LgpdController extends Controller
{
    public function __construct(
        private readonly PatientAccountResolver $accountResolver,
        private readonly DataSubjectRequestService $requestService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $requests = DataSubjectRequest::query()
            ->where('user_id', $request->user()->id)
            ->with('patient:id,name')
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 20));

        return response()->json([
            'data' => $requests->getCollection()->map(fn (DataSubjectRequest $dsr) => [
                'id' => $dsr->id,
                'type' => $dsr->type->value,
                'type_label' => $dsr->type->label(),
                'status' => $dsr->status->value,
                'details' => $dsr->details,
                'patient_name' => $dsr->patient?->name,
                'created_at' => $dsr->created_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $allowedPatientIds = $this->accountResolver->patientsForUser($user)->pluck('id')->all();

        $validated = $request->validate([
            'type' => ['required', Rule::enum(DataSubjectRequestType::class)],
            'details' => ['nullable', 'string', 'max:5000'],
            'patient_id' => ['nullable', 'integer', Rule::in($allowedPatientIds)],
        ]);

        try {
            $record = $this->requestService->create($user, $validated, $request);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('Solicitação registrada.'),
            'data' => [
                'id' => $record->id,
                'type' => $record->type->value,
                'status' => $record->status->value,
            ],
        ], 201);
    }
}
