<?php

namespace App\Services;

use App\Models\User;
use App\Support\ImageUploadOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InstitutionLogoService
{
    public function __construct(
        private readonly ImageUploadOptimizer $optimizer,
    ) {}

    public function store(User $owner, UploadedFile $file): void
    {
        $this->deleteStoredFile($owner);

        $mime = (string) $file->getMimeType();
        $isSvg = $mime === 'image/svg+xml' || str_ends_with(strtolower($file->getClientOriginalName()), '.svg');

        if ($isSvg) {
            $path = $file->storeAs(
                $this->storageDirectory($owner),
                Str::uuid()->toString().'.svg',
                'public'
            );
            $owner->institution_logo_path = $path;

            return;
        }

        $optimized = $this->optimizer->optimize($file, maxEdge: 1600, quality: 88);

        try {
            $path = $optimized->storeAs(
                $this->storageDirectory($owner),
                Str::uuid()->toString().'.jpg',
                'public'
            );
        } finally {
            if ($optimized !== $file) {
                $this->optimizer->cleanupTemp($optimized);
            }
        }

        $owner->institution_logo_path = $path;
    }

    public function remove(User $owner): void
    {
        $this->deleteStoredFile($owner);
        $owner->institution_logo_path = null;
    }

    public function deleteStoredFile(User $owner): void
    {
        if (blank($owner->institution_logo_path)) {
            return;
        }

        Storage::disk('public')->delete($owner->institution_logo_path);
    }

    private function storageDirectory(User $owner): string
    {
        return 'institution-logos/'.$owner->id;
    }
}
