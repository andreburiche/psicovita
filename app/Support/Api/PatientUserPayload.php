<?php

namespace App\Support\Api;

use App\Models\User;

final class PatientUserPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function make(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role->value,
            'avatar_url' => $user->avatarUrl(),
            'uses_patient_portal_experience' => $user->usesPatientPortalExperience(),
            'email_verified' => $user->hasVerifiedEmail(),
        ];
    }
}
