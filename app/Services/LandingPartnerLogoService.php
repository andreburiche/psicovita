<?php

namespace App\Services;

use App\Models\LandingPartner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LandingPartnerLogoService
{
    public function store(LandingPartner $partner, UploadedFile $file): void
    {
        $this->deleteStoredFile($partner);

        $extension = strtolower($file->extension() ?: 'png');
        $path = $file->storeAs(
            $this->storageDirectory($partner),
            Str::uuid()->toString().'.'.$extension,
            'public'
        );

        $partner->logo_path = $path;
    }

    public function remove(LandingPartner $partner): void
    {
        $this->deleteStoredFile($partner);
        $partner->logo_path = null;
    }

    public function deleteStoredFile(LandingPartner $partner): void
    {
        if (blank($partner->logo_path)) {
            return;
        }

        Storage::disk('public')->delete($partner->logo_path);
    }

    private function storageDirectory(LandingPartner $partner): string
    {
        return 'landing-partners/'.$partner->id;
    }
}
