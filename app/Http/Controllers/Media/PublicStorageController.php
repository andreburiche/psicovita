<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serve ficheiros do disk "public" via /storage/... quando o symlink
 * public/storage não existe (comum em hospedagem partilhada).
 */
class PublicStorageController extends Controller
{
    /** @var list<string> */
    private const ALLOWED_PREFIXES = [
        'avatars/',
        'institution-logos/',
        'landing-partners/',
        'professional-files/',
        'patient-documents/',
        'document-request-files/',
        'pix-qrcodes/',
    ];

    public function show(string $path): StreamedResponse
    {
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..') || ! $this->isAllowed($path)) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'Content-Disposition' => 'inline',
        ]);
    }

    private function isAllowed(string $path): bool
    {
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
