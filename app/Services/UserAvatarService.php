<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use App\Support\ImageUploadOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserAvatarService
{
    public function __construct(
        private readonly ImageUploadOptimizer $optimizer,
    ) {}

    public function store(User|Patient $owner, UploadedFile $file): void
    {
        $this->deleteStoredFile($owner);

        $optimized = $this->optimizer->optimize($file, maxEdge: 512, quality: 90);

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

        $owner->avatar_path = $path;
    }

    public function remove(User|Patient $owner): void
    {
        $this->deleteStoredFile($owner);
        $owner->avatar_path = null;
    }

    public function deleteStoredFile(User|Patient $owner): void
    {
        if ($owner->avatar_path === null || $owner->avatar_path === '') {
            return;
        }

        Storage::disk('public')->delete($owner->avatar_path);
    }

    private function storageDirectory(User|Patient $owner): string
    {
        if ($owner instanceof User) {
            return 'avatars/'.$owner->id;
        }

        return 'avatars/patients/'.$owner->id;
    }
}
