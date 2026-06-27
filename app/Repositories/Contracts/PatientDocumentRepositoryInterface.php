<?php

namespace App\Repositories\Contracts;

use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface PatientDocumentRepositoryInterface
{
    /** @return Collection<int, PatientDocument> */
    public function listForPatient(Patient $patient): Collection;

    public function store(
        Patient $patient,
        UploadedFile $file,
        array $attributes,
        int $uploadedByUserId,
    ): PatientDocument;

    public function findOrFail(int $id): PatientDocument;

    public function delete(PatientDocument $document): void;
}
