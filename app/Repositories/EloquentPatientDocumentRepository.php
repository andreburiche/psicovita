<?php

namespace App\Repositories;

use App\Models\Patient;
use App\Models\PatientDocument;
use App\Repositories\Contracts\PatientDocumentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EloquentPatientDocumentRepository implements PatientDocumentRepositoryInterface
{
    public function listForPatient(Patient $patient): Collection
    {
        return PatientDocument::query()
            ->where('patient_id', $patient->id)
            ->with(['documentRequest', 'uploader'])
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->get();
    }

    public function store(Patient $patient, UploadedFile $file, array $attributes, int $uploadedByUserId): PatientDocument
    {
        $directory = sprintf('patient-documents/%d', $patient->id);
        $safeName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $safeName, 'local');

        return PatientDocument::query()->create(array_merge($attributes, [
            'patient_id' => $patient->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $uploadedByUserId,
        ]));
    }

    public function findOrFail(int $id): PatientDocument
    {
        return PatientDocument::query()
            ->with(['patient', 'documentRequest'])
            ->findOrFail($id);
    }

    public function delete(PatientDocument $document): void
    {
        $document->deleteStoredFile();
        $document->delete();
    }
}
