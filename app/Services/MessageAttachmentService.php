<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageAttachmentService
{
    /** @var list<string> */
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'audio/ogg',
        'audio/mpeg',
        'audio/mp4',
        'video/mp4',
    ];

    public function store(Message $message, UploadedFile $file): MessageAttachment
    {
        $mime = (string) $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException(__('Tipo de ficheiro não permitido.'));
        }

        $original = $file->getClientOriginalName();
        $safeName = Str::uuid()->toString().'_'.Str::slug(pathinfo($original, PATHINFO_FILENAME)).'.'.($file->extension() ?: 'bin');
        $directory = 'conversation-attachments/'.$message->conversation_id;
        $path = $file->storeAs($directory, $safeName, 'local');

        return MessageAttachment::query()->create([
            'message_id' => $message->id,
            'original_name' => $original,
            'mime_type' => $mime,
            'size_bytes' => (int) $file->getSize(),
            'disk' => 'local',
            'path' => $path,
        ]);
    }

    /**
     * @return MessageAttachment|null null se MIME não permitido ou falha ao gravar
     */
    public function storeFromBinary(Message $message, string $contents, string $mimeType, string $originalName): ?MessageAttachment
    {
        $mime = trim($mimeType) ?: 'application/octet-stream';

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            Log::info('Anexo WhatsApp ignorado — MIME não permitido', [
                'mime' => $mime,
                'message_id' => $message->id,
            ]);

            return null;
        }

        $original = trim($originalName) !== '' ? trim($originalName) : 'whatsapp-anexo.bin';
        $extension = pathinfo($original, PATHINFO_EXTENSION) ?: $this->extensionForMime($mime) ?: 'bin';
        $safeName = Str::uuid()->toString().'_'.Str::slug(pathinfo($original, PATHINFO_FILENAME)).'.'.$extension;
        $directory = 'conversation-attachments/'.$message->conversation_id;
        $path = $directory.'/'.$safeName;

        if (! Storage::disk('local')->put($path, $contents)) {
            return null;
        }

        return MessageAttachment::query()->create([
            'message_id' => $message->id,
            'original_name' => $original,
            'mime_type' => $mime,
            'size_bytes' => strlen($contents),
            'disk' => 'local',
            'path' => $path,
        ]);
    }

    private function extensionForMime(string $mime): ?string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'audio/mp4' => 'm4a',
            'video/mp4' => 'mp4',
            default => null,
        };
    }
}
