<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

final class ClinicalPracticeBrand
{
    public static function practiceOwner(User $actor): User
    {
        if ($actor->isClinicTeamMember()) {
            $owner = User::query()->find($actor->clinicalPracticeId());

            return $owner ?? $actor;
        }

        return $actor;
    }

    public static function logoAbsolutePath(User $actor): ?string
    {
        $owner = self::practiceOwner($actor);

        if (filled($owner->institution_logo_path) && Storage::disk('public')->exists($owner->institution_logo_path)) {
            return Storage::disk('public')->path($owner->institution_logo_path);
        }

        return PortalBrand::logoAbsolutePath();
    }

    public static function logoDataUri(User $actor): ?string
    {
        return PortalBrand::fileToDataUri(self::logoAbsolutePath($actor));
    }

    public static function institutionName(User $actor): string
    {
        return self::practiceOwner($actor)->name;
    }

    public static function usesCustomLogo(User $actor): bool
    {
        $owner = self::practiceOwner($actor);

        return filled($owner->institution_logo_path)
            && Storage::disk('public')->exists($owner->institution_logo_path);
    }
}
