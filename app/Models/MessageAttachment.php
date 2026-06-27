<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    protected $fillable = [
        'message_id',
        'original_name',
        'mime_type',
        'size_bytes',
        'disk',
        'path',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function humanSize(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0).' KB';
        }

        return $bytes.' B';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    protected static function booted(): void
    {
        static::deleting(function (MessageAttachment $attachment) {
            if ($attachment->path && Storage::disk($attachment->disk)->exists($attachment->path)) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
        });
    }
}
