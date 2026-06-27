<?php

namespace App\Services\Auth;

use App\Enums\UserProfessionalFunction;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Support\ContactHasher;
use Illuminate\Support\Str;

class UserRegistrationService
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * @return array{becomes_patient: bool, role: UserRole, professional_id: ?int}
     */
    public function resolveRoleForEmail(string $email): array
    {
        $normalized = Str::lower(trim($email));

        $distinctProsWithPatientEmail = Patient::query()
            ->where('email_hash', ContactHasher::emailHash($normalized))
            ->distinct()
            ->pluck('professional_id');

        $becomesPatient = $distinctProsWithPatientEmail->count() === 1;

        return [
            'becomes_patient' => $becomesPatient,
            'role' => $becomesPatient ? UserRole::Patient : UserRole::Professional,
            'professional_id' => $becomesPatient ? (int) $distinctProsWithPatientEmail->first() : null,
        ];
    }

    /**
     * @param  array{name: string, email: string, password?: ?string, role: UserRole, professional_id?: ?int, professional_function?: ?UserProfessionalFunction, email_verified?: bool}  $attributes
     */
    public function createUser(array $attributes): User
    {
        $user = User::create([
            'name' => $attributes['name'],
            'email' => Str::lower(trim($attributes['email'])),
            'password' => $attributes['password'] ?? null,
            'role' => $attributes['role'],
            'professional_id' => $attributes['professional_id'] ?? null,
            'professional_function' => $attributes['professional_function'] ?? null,
        ]);

        if ($attributes['email_verified'] ?? false) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($user->isProfessional()) {
            $this->subscriptions->startTrial($user);
        }

        return $user;
    }

    public function linkSocialAccount(User $user, string $provider, string $providerId, ?string $avatar = null): SocialAccount
    {
        $existing = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($existing !== null && (int) $existing->user_id !== (int) $user->id) {
            throw new \RuntimeException(__('Esta conta social já está associada a outro utilizador.'));
        }

        return SocialAccount::query()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $providerId,
            ],
            [
                'user_id' => $user->id,
                'avatar' => $avatar,
            ],
        );
    }
}
