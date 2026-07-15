<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvatarMediaController extends Controller
{
    public function user(User $user): StreamedResponse|Response
    {
        return $this->streamAvatar($user->avatar_path, $user->updated_at?->timestamp);
    }

    public function patient(Patient $patient): StreamedResponse|Response
    {
        return $this->streamAvatar($patient->avatar_path, $patient->updated_at?->timestamp);
    }

    private function streamAvatar(?string $path, ?int $version): StreamedResponse|Response
    {
        if ($path === null || $path === '' || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'Content-Disposition' => 'inline',
            'ETag' => '"'.sha1($path.'|'.(string) $version).'"',
        ]);
    }
}
