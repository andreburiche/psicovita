<?php

namespace App\Services;

use App\Enums\DocumentRequestFileCategory;
use App\Enums\DocumentRequestStatus;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestAccessLog;
use App\Models\DocumentRequestFile;
use App\Models\Patient;
use App\Models\User;
use App\Repositories\Contracts\DocumentRequestFileRepositoryInterface;
use App\Repositories\Contracts\DocumentRequestRepositoryInterface;
use App\Support\AuditTrail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DocumentRequestService
{
    public function __construct(
        private readonly DocumentRequestRepositoryInterface $requests,
        private readonly DocumentRequestFileRepositoryInterface $files,
        private readonly DocumentRequestAccessLogService $accessLog,
        private readonly PatientDocumentService $patientDocuments,
    ) {}

    public function paginateForPatient(Patient $patient, int $perPage = 15): LengthAwarePaginator
    {
        return $this->requests->paginateForPatient($patient, $perPage);
    }

    /** @return Collection<int, DocumentRequest> */
    public function listForPatient(Patient $patient): Collection
    {
        return $this->requests->listForPatient($patient);
    }

    public function findForPatient(Patient $patient, int $id): DocumentRequest
    {
        return $this->requests->findForPatient($patient, $id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Patient $patient, User $actor, array $data): DocumentRequest
    {
        $payload = $this->normalizePayload($data, $patient, $actor, isCreate: true);

        $request = $this->requests->create($payload);

        $this->accessLog->record($request, DocumentRequestAccessLog::ACTION_CREATED, $actor);
        AuditTrail::entity('create', 'document_requests', $request, null, $actor);

        $this->storeUploadsFromData($request, $actor, $data);

        if ($request->authorization_attached) {
            $request->load('files');
        }

        return $request->fresh(['files', 'createdByUser', 'updatedByUser', 'consentRecordedByUser']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DocumentRequest $documentRequest, User $actor, array $data): DocumentRequest
    {
        $payload = $this->normalizePayload($data, $documentRequest->patient, $actor, isCreate: false);
        $payload['updated_by'] = $actor->id;

        $before = $documentRequest->only(array_keys($payload));
        $request = $this->requests->update($documentRequest, $payload);

        $this->accessLog->record($request, DocumentRequestAccessLog::ACTION_UPDATED, $actor);
        AuditTrail::entity('update', 'document_requests', $request, [
            'before' => $before,
            'after' => $payload,
        ], $actor);

        $this->storeUploadsFromData($request, $actor, $data);

        return $request->fresh(['files', 'createdByUser', 'updatedByUser', 'consentRecordedByUser']);
    }

    public function delete(DocumentRequest $documentRequest, User $actor): void
    {
        foreach ($documentRequest->files as $file) {
            $this->files->delete($file);
        }

        $this->accessLog->record($documentRequest, DocumentRequestAccessLog::ACTION_DELETED, $actor);
        AuditTrail::entity('delete', 'document_requests', $documentRequest, null, $actor);

        $this->requests->softDelete($documentRequest, $actor->id);
    }

    public function recordView(DocumentRequest $documentRequest, User $actor): void
    {
        $this->accessLog->record($documentRequest, DocumentRequestAccessLog::ACTION_VIEWED, $actor);
        AuditTrail::entity('view', 'document_requests', $documentRequest, null, $actor);
    }

    public function storeFile(
        DocumentRequest $documentRequest,
        UploadedFile $uploadedFile,
        DocumentRequestFileCategory $category,
        User $actor,
    ): DocumentRequestFile {
        $file = $this->files->store($documentRequest, $uploadedFile, $category, $actor->id);

        if ($category === DocumentRequestFileCategory::Authorization) {
            $documentRequest->update(['authorization_attached' => true]);
        }

        $this->accessLog->record($documentRequest, DocumentRequestAccessLog::ACTION_FILE_UPLOADED, $actor);

        $this->patientDocuments->attach($documentRequest->patient, $actor, $uploadedFile, [
            'title' => $documentRequest->institution_name.' — '.$category->label(),
            'category' => $category->value,
            'document_request_id' => $documentRequest->id,
            'received_at' => now()->toDateString(),
            'notes' => null,
        ]);

        return $file;
    }

    public function deleteFile(DocumentRequestFile $file, User $actor): void
    {
        $request = $file->documentRequest;
        $this->files->delete($file);

        if ($request->files()->where('category', DocumentRequestFileCategory::Authorization)->doesntExist()) {
            $request->update(['authorization_attached' => false]);
        }

        $this->accessLog->record($request, DocumentRequestAccessLog::ACTION_DELETED, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data, Patient $patient, User $actor, bool $isCreate): array
    {
        $documents = array_values(array_filter(array_map(
            fn ($item) => is_string($item) ? trim($item) : '',
            (array) ($data['requested_documents'] ?? [])
        )));

        if (filled($data['requested_documents_other'] ?? null)) {
            $documents[] = trim((string) $data['requested_documents_other']);
        }

        $payload = [
            'patient_id' => $patient->id,
            'professional_id' => $actor->tenantProfessionalId() ?? $actor->id,
            'institution_name' => trim((string) $data['institution_name']),
            'institution_type' => $data['institution_type'],
            'contact_name' => $data['contact_name'] ?? null,
            'contact_email' => isset($data['contact_email']) ? Str::lower(trim((string) $data['contact_email'])) : null,
            'contact_phone' => $this->normalizePhone($data['contact_phone'] ?? null),
            'requested_documents' => $documents,
            'request_reason' => $data['request_reason'] ?? null,
            'authorization_attached' => (bool) ($data['authorization_attached'] ?? false),
            'request_date' => $data['request_date'],
            'expected_return_date' => $data['expected_return_date'] ?? null,
            'status' => $data['status'] ?? DocumentRequestStatus::Pending->value,
            'notes' => $data['notes'] ?? null,
        ];

        if (! empty($data['patient_consent_confirmed'])) {
            $payload['patient_consent_at'] = now();
            $payload['patient_consent_recorded_by'] = $actor->id;
        }

        if ($isCreate) {
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeUploadsFromData(DocumentRequest $request, User $actor, array $data): void
    {
        $map = [
            'authorization_file' => DocumentRequestFileCategory::Authorization,
            'institution_file' => DocumentRequestFileCategory::InstitutionResponse,
            'complementary_file' => DocumentRequestFileCategory::ComplementaryReport,
        ];

        foreach ($map as $key => $category) {
            $uploaded = Arr::get($data, $key);
            if ($uploaded instanceof UploadedFile) {
                $this->storeFile($request, $uploaded, $category, $actor);
            }
        }
    }

    private function normalizePhone(mixed $phone): ?string
    {
        if (! is_string($phone)) {
            return null;
        }

        $digits = only_digits($phone);

        return $digits === '' ? null : $digits;
    }
}
