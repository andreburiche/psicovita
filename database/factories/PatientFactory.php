<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'professional_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'birth_date' => fake()->optional()->date(),
            'cpf' => null,
            'address_postal_code' => null,
            'address_street' => null,
            'address_number' => null,
            'address_complement' => null,
            'address_district' => null,
            'address_city' => null,
            'address_state' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
