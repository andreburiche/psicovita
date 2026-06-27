<?php

namespace Database\Factories;

use App\Models\ScheduleBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleBlock>
 */
class ScheduleBlockFactory extends Factory
{
    protected $model = ScheduleBlock::class;

    public function definition(): array
    {
        return [
            'professional_id' => User::factory(),
            'block_date' => fake()->dateTimeBetween('now', '+2 weeks')->format('Y-m-d'),
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'reason' => fake()->optional()->sentence(3),
        ];
    }
}
