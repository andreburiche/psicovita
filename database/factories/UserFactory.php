<?php

namespace Database\Factories;

use App\Enums\UserProfessionalFunction;
use App\Enums\UserRole;
use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\User $user): void {
        if ($user->isProfessional() && $user->clinic_owner_id === null) {
                app(SubscriptionService::class)->startTrial($user);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'remember_token' => Str::random(10),
            'role' => UserRole::Professional,
            'professional_id' => null,
            'professional_function' => UserProfessionalFunction::Psychologist,
            'phone' => null,
            'whatsapp_notifications' => false,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withoutPassword(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => null,
        ]);
    }
}
