<?php

namespace Database\Factories;

use App\Enums\SessionMode;
use App\Enums\TherapySessionStatus;
use App\Enums\TherapySessionType;
use App\Models\Patient;
use App\Models\TherapySession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TherapySession>
 */
class TherapySessionFactory extends Factory
{
    protected $model = TherapySession::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'professional_id' => fn (array $attributes) => Patient::query()->find($attributes['patient_id'])->professional_id,
            'session_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'session_time' => fake()->randomElement(['09:00:00', '10:00:00', '14:00:00', '16:00:00']),
            'status' => fake()->randomElement(TherapySessionStatus::cases()),
            'type' => fake()->randomElement(TherapySessionType::cases()),
            'session_mode' => SessionMode::Individual,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
