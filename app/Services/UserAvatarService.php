<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserAvatarService
{
    public function store(User|Patient $owner, UploadedFile $file): void
    {
        $this->deleteStoredFile($owner);

        $path = $file->storeAs(
            $this->storageDirectory($owner),
            Str::uuid()->toString().'.jpg',
            'public'
        );

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
