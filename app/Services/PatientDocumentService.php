<?php

namespace App\Services;

use App\Enums\DocumentRequestFileCategory;
use App\Enums\DocumentRequestStatus;
use App\Models\DocumentRequest;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
use App\Repositories\Contracts\DocumentRequestRepositoryInterface;
use App\Repositories\Contracts\PatientDocumentRepositoryInterface;
use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

class PatientDocumentService
{
    public function __construct(
        private readonly PatientDocumentRepositoryInterface $documents,
        private readonly DocumentRequestRepositoryInterface $documentRequests,
        private readonly DocumentRequestAccessLogService $accessLog,
    ) {}

    /** @return Collection<int, PatientDocument> */
    public function listForPatient(Patient $patient): Collection
    {
        return $this->documents->listForPatient($patient);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function attach(Patient $patient, User $actor, UploadedFile $file, array $data): PatientDocument
    {
        $documentRequest = null;
        if (! empty($data['document_request_id'])) {
            $documentRequest = $this->documentRequests->findForPatient(
                $patient,
                (int) $data['document_request_id']
            );
        }

        $category = DocumentRequestFileCategory::from((string) $data['category']);

        $document = $this->documents->store($patient, $file, [
            'professional_id' => $actor->tenantProfessionalId() ?? $actor->id,
            'document_request_id' => $documentRequest?->id,
            'title' => trim((string) $data['title']),
            'category' => $category->value,
            'received_at' => $data['received_at'] ?? now()->toDateString(),
            'notes' => $data['notes'] ?? null,
        ], $actor->id);

        if ($documentRequest !== null) {
            $this->markRequestAnsweredIfDevolutiva($documentRequest, $category, $actor);
            $this->accessLog->record($documentRequest, \App\Models\DocumentRequestAccessLog::ACTION_FILE_UPLOADED, $actor);
        }

        AuditTrail::entity('create', 'patient_documents', $document, null, $actor);

        return $document->fresh(['documentRequest', 'uploader']);
    }

    public function delete(PatientDocument $document, User $actor): void
    {
        if ($document->document_request_id) {
            $request = $document->documentRequest;
            if ($request) {
                $this->accessLog->record($request, \App\Models\DocumentRequestAccessLog::ACTION_DELETED, $actor);
            }
        }

        AuditTrail::entity('delete', 'patient_documents', $document, null, $actor);
        $this->documents->delete($document);
    }

    private function markRequestAnsweredIfDevolutiva(
        DocumentRequest $documentRequest,
        DocumentRequestFileCategory $category,
        User $actor,
    ): void {
        if ($category !== DocumentRequestFileCategory::InstitutionResponse) {
            return;
        }

        if ($documentRequest->status === DocumentRequestStatus::Answered) {
            return;
        }

        $this->documentRequests->update($documentRequest, [
            'status' => DocumentRequestStatus::Answered,
            'updated_by' => $actor->id,
        ]);
    }
}
