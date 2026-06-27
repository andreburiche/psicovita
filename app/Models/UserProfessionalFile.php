<?php

namespace App\Models;

use App\Enums\UserProfessionalFileCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UserProfessionalFile extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'category',
        'original_name',
        'file_path',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'category' => UserProfessionalFileCategory::class,
            'size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (UserProfessionalFile $file) {
            $file->deleteStoredFile();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deleteStoredFile(): void
    {
        if (filled($this->file_path)) {
            Storage::disk('local')->delete($this->file_path);
        }
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / (1024 * 1024), 1).' MB';
    }
}
