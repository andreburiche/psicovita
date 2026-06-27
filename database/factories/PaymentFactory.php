<?php

namespace Database\Factories;

use App\Enums\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'therapy_session_id' => null,
            'amount' => fake()->randomFloat(2, 80, 400),
            'status' => fake()->randomElement(PaymentStatus::cases()),
            'gateway' => PaymentGateway::Manual,
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'notes' => null,
        ];
    }
}
