<?php

namespace Database\Factories;

use App\Models\ScheduledScan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScheduledScan>
 */
class ScheduledScanFactory extends Factory
{
    protected $model = ScheduledScan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => $this->faker->url(),
            'name' => $this->faker->sentence(3),
            'frequency' => 'daily',
            'scheduled_time' => '09:00',
            'is_active' => true,
            'total_runs' => 0,
        ];
    }

    /**
     * Weekly frequency.
     */
    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'weekly',
            'day_of_week' => $this->faker->numberBetween(0, 6),
        ]);
    }

    /**
     * Monthly frequency.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
            'day_of_month' => $this->faker->numberBetween(1, 28),
        ]);
    }

    /**
     * Inactive scheduled scan.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
