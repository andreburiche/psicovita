<?php

namespace App\Repositories;

use App\Enums\DocumentRequestFileCategory;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestFile;
use App\Repositories\Contracts\DocumentRequestFileRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EloquentDocumentRequestFileRepository implements DocumentRequestFileRepositoryInterface
{
    public function store(
        DocumentRequest $documentRequest,
        UploadedFile $file,
        DocumentRequestFileCategory $category,
        int $uploadedByUserId,
    ): DocumentRequestFile {
        $directory = sprintf('document-requests/%d', $documentRequest->id);
        $safeName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $safeName, 'local');

        return DocumentRequestFile::query()->create([
            'document_request_id' => $documentRequest->id,
            'category' => $category,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $uploadedByUserId,
        ]);
    }

    public function findOrFail(int $id): DocumentRequestFile
    {
        return DocumentRequestFile::query()->with('documentRequest.patient')->findOrFail($id);
    }

    public function delete(DocumentRequestFile $file): void
    {
        $file->deleteStoredFile();
        $file->delete();
    }
}
