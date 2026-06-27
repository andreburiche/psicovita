<?php

namespace App\Services\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use App\Support\SocialAuthProvider;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthService
{
    public function __construct(
        private readonly UserRegistrationService $registration,
    ) {}

    public function findUserBySocialAccount(string $provider, string $providerId): ?User
    {
        $account = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        return $account?->user;
    }

    /**
     * Resolve an existing user or return null when registration must be completed.
     */
    public function resolveExistingUser(string $provider, SocialiteUser $socialUser): ?User
    {
        $providerId = (string) $socialUser->getId();
        $email = Str::lower(trim((string) $socialUser->getEmail()));

        if ($email === '') {
            return null;
        }

        $bySocial = $this->findUserBySocialAccount($provider, $providerId);
        if ($bySocial !== null) {
            $this->registration->linkSocialAccount($bySocial, $provider, $providerId, $socialUser->getAvatar());

            return $bySocial;
        }

        $byEmail = User::findByEmail($email);
        if ($byEmail !== null) {
            $this->registration->linkSocialAccount($byEmail, $provider, $providerId, $socialUser->getAvatar());

            if ($byEmail->email_verified_at === null) {
                $byEmail->forceFill(['email_verified_at' => now()])->save();
            }

            return $byEmail;
        }

        return null;
    }

    /**
     * @return array{
     *     provider: string,
     *     provider_id: string,
     *     email: string,
     *     name: string,
     *     avatar: ?string,
     *     becomes_patient: bool,
     *     role: string,
     *     professional_id: ?int,
     * }
     */
    public function registrationPayload(string $provider, SocialiteUser $socialUser): array
    {
        $email = Str::lower(trim((string) $socialUser->getEmail()));
        $roleContext = $this->registration->resolveRoleForEmail($email);

        return [
            'provider' => $provider,
            'provider_id' => (string) $socialUser->getId(),
            'email' => $email,
            'name' => trim((string) ($socialUser->getName() ?: Str::before($email, '@'))),
            'avatar' => $socialUser->getAvatar(),
            'becomes_patient' => $roleContext['becomes_patient'],
            'role' => $roleContext['role']->value,
            'professional_id' => $roleContext['professional_id'],
        ];
    }

    public function isRegistrationSessionValid(?array $payload): bool
    {
        if ($payload === null || $payload === []) {
            return false;
        }

        foreach (['provider', 'provider_id', 'email', 'name', 'role'] as $key) {
            if (! filled($payload[$key] ?? null)) {
                return false;
            }
        }

        return SocialAuthProvider::isValid((string) $payload['provider']);
    }
}
