<?php

namespace Database\Factories;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scan>
 */
class ScanFactory extends Factory
{
    protected $model = Scan::class;

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
            'title' => $this->faker->sentence(3),
            'status' => 'completed',
            'score' => $this->faker->randomFloat(1, 0, 100),
            'grade' => $this->faker->randomElement(['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'F']),
            'requested_tier' => 'basic',
            'results' => [
                'pillars' => [],
                'recommendations' => [],
                'summary' => [],
                'max_score' => 100,
            ],
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ];
    }

    /**
     * Indicate that the scan is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'score' => 0,
            'grade' => 'F',
            'results' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the scan is in progress.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'score' => 0,
            'grade' => 'F',
            'results' => null,
            'completed_at' => null,
            'progress_step' => 'Analyzing content...',
            'progress_percent' => 50,
        ]);
    }

    /**
     * Indicate that the scan failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'score' => 0,
            'grade' => 'F',
            'results' => null,
            'error_message' => 'Failed to analyze the URL.',
            'completed_at' => now(),
        ]);
    }

    /**
     * Set the scan tier.
     */
    public function tier(string $tier): static
    {
        $maxScores = [
            'basic' => 100,
            'pro' => 135,
            'full' => 175,
        ];

        return $this->state(fn (array $attributes) => [
            'requested_tier' => $tier,
            'results' => array_merge($attributes['results'] ?? [], [
                'max_score' => $maxScores[$tier] ?? 100,
            ]),
        ]);
    }
}
