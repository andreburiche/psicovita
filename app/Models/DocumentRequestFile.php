<?php

namespace App\Models;

use App\Enums\DocumentRequestFileCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentRequestFile extends Model
{
    protected $fillable = [
        'document_request_id',
        'category',
        'original_name',
        'file_path',
        'mime_type',
        'size_bytes',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => DocumentRequestFileCategory::class,
        ];
    }

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function deleteStoredFile(): void
    {
        if (filled($this->file_path)) {
            Storage::disk('local')->delete($this->file_path);
        }
    }
}
