<?php

namespace App\Repositories\Contracts;

use App\Models\DocumentRequest;
use App\Models\Patient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DocumentRequestRepositoryInterface
{
    public function paginateForPatient(Patient $patient, int $perPage = 15): LengthAwarePaginator;

    /** @return Collection<int, DocumentRequest> */
    public function listForPatient(Patient $patient): Collection;

    public function findForPatient(Patient $patient, int $id): DocumentRequest;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): DocumentRequest;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(DocumentRequest $documentRequest, array $attributes): DocumentRequest;

    public function softDelete(DocumentRequest $documentRequest, int $deletedByUserId): void;
}
