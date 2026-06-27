<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InstitutionLogoService
{
    public function store(User $owner, UploadedFile $file): void
    {
        $this->deleteStoredFile($owner);

        $extension = strtolower($file->extension() ?: 'png');
        $path = $file->storeAs(
            $this->storageDirectory($owner),
            Str::uuid()->toString().'.'.$extension,
            'public'
        );

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
