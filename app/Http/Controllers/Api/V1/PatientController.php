<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\PatientService;
use App\Support\AuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientController extends Controller
{
    public function __construct(
        private readonly PatientService $patientService
    ) {
        $this->authorizeResource(Patient::class, 'patient');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $patients = $this->patientService->paginateForProfessional(
            $request->user(),
            $request->string('q')->toString() ?: null,
            (int) $request->query('per_page', 15)
        );

        return PatientResource::collection($patients);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $patient = $this->patientService->create($request->user(), $validated);

        AuditTrail::entity('create', 'patients', $patient, null, $request->user());

        return PatientResource::make($patient)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Patient $patient): PatientResource
    {
        AuditTrail::entity('view', 'patients', $patient, null, $request->user());

        return new PatientResource($patient);
    }

    public function update(Request $request, Patient $patient): PatientResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->patientService->update($patient, $validated);

        AuditTrail::entity('update', 'patients', $patient, null, $request->user());

        return new PatientResource($patient->fresh());
    }

    public function destroy(Request $request, Patient $patient): Response
    {
        AuditTrail::entity('delete', 'patients', $patient, null, $request->user());

        $this->patientService->delete($patient);

        return response()->noContent();
    }
}
