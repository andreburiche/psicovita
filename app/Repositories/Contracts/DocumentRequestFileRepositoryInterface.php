<?php

namespace App\Repositories\Contracts;

use App\Enums\DocumentRequestFileCategory;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestFile;
use Illuminate\Http\UploadedFile;

interface DocumentRequestFileRepositoryInterface
{
    public function store(
        DocumentRequest $documentRequest,
        UploadedFile $file,
        DocumentRequestFileCategory $category,
        int $uploadedByUserId,
    ): DocumentRequestFile;

    public function findOrFail(int $id): DocumentRequestFile;

    public function delete(DocumentRequestFile $file): void;
}
