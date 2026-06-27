<?php

namespace App\Models;

use App\Enums\DocumentRequestFileCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PatientDocument extends Model
{
    protected $fillable = [
        'patient_id',
        'professional_id',
        'document_request_id',
        'title',
        'category',
        'original_name',
        'file_path',
        'mime_type',
        'size_bytes',
        'received_at',
        'notes',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => DocumentRequestFileCategory::class,
            'received_at' => 'date',
            'notes' => 'encrypted',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
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
