<?php

namespace App\Repositories;

use App\Models\DocumentRequest;
use App\Models\Patient;
use App\Repositories\Contracts\DocumentRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentDocumentRequestRepository implements DocumentRequestRepositoryInterface
{
    public function paginateForPatient(Patient $patient, int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($patient)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listForPatient(Patient $patient): Collection
    {
        return $this->baseQuery($patient)->get();
    }

    public function findForPatient(Patient $patient, int $id): DocumentRequest
    {
        return $this->baseQuery($patient)->findOrFail($id);
    }

    public function create(array $attributes): DocumentRequest
    {
        return DocumentRequest::query()->create($attributes);
    }

    public function update(DocumentRequest $documentRequest, array $attributes): DocumentRequest
    {
        $documentRequest->fill($attributes);
        $documentRequest->save();

        return $documentRequest->fresh(['files', 'createdByUser', 'updatedByUser']);
    }

    public function softDelete(DocumentRequest $documentRequest, int $deletedByUserId): void
    {
        $documentRequest->deleted_by = $deletedByUserId;
        $documentRequest->save();
        $documentRequest->delete();
    }

    /** @return \Illuminate\Database\Eloquent\Builder<DocumentRequest> */
    private function baseQuery(Patient $patient): \Illuminate\Database\Eloquent\Builder
    {
        return DocumentRequest::query()
            ->where('patient_id', $patient->id)
            ->with(['files', 'createdByUser', 'updatedByUser'])
            ->orderByDesc('request_date')
            ->orderByDesc('id');
    }
}
