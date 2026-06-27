<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClinicalRecordResource;
use App\Models\ClinicalRecord;
use App\Models\RecordAccessLog;
use App\Support\AuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ClinicalRecordController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ClinicalRecord::class, 'clinical_record');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $records = ClinicalRecord::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->with('patient')
            ->latest()
            ->paginate((int) $request->query('per_page', 20))
            ->withQueryString();

        return ClinicalRecordResource::collection($records);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
            'content' => ['required', 'string', 'max:50000'],
        ]);

        $record = ClinicalRecord::query()->create([
            ...$validated,
            'professional_id' => $request->user()->clinicalPracticeId(),
        ])->load('patient');

        RecordAccessLog::query()->create([
            'user_id' => $request->user()->clinicalPracticeId(),
            'clinical_record_id' => $record->id,
            'action' => RecordAccessLog::ACTION_CREATED,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return ClinicalRecordResource::make($record)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, ClinicalRecord $clinicalRecord): ClinicalRecordResource
    {
        $clinicalRecord->load('patient');

        RecordAccessLog::query()->create([
            'user_id' => $request->user()->clinicalPracticeId(),
            'clinical_record_id' => $clinicalRecord->id,
            'action' => RecordAccessLog::ACTION_VIEWED,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return new ClinicalRecordResource($clinicalRecord);
    }

    public function update(Request $request, ClinicalRecord $clinicalRecord): ClinicalRecordResource
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:50000'],
        ]);

        $clinicalRecord->update($validated);

        RecordAccessLog::query()->create([
            'user_id' => $request->user()->clinicalPracticeId(),
            'clinical_record_id' => $clinicalRecord->id,
            'action' => RecordAccessLog::ACTION_UPDATED,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return new ClinicalRecordResource($clinicalRecord->fresh()->load('patient'));
    }

    public function destroy(Request $request, ClinicalRecord $clinicalRecord): Response
    {
        RecordAccessLog::query()->create([
            'user_id' => $request->user()->clinicalPracticeId(),
            'clinical_record_id' => $clinicalRecord->id,
            'action' => RecordAccessLog::ACTION_DELETED,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        AuditTrail::entity('delete', 'clinical_records', $clinicalRecord, null, $request->user());

        $clinicalRecord->delete();

        return response()->noContent();
    }
}
