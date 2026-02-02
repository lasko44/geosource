<?php

namespace Database\Factories;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CitationCheck>
 */
class CitationCheckFactory extends Factory
{
    protected $model = CitationCheck::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'citation_query_id' => CitationQuery::factory(),
            'user_id' => User::factory(),
            'platform' => $this->faker->randomElement(['perplexity', 'openai', 'claude', 'gemini', 'deepseek']),
            'status' => 'completed',
            'is_cited' => $this->faker->boolean(),
            'citations' => [],
            'started_at' => now()->subMinutes(1),
            'completed_at' => now(),
        ];
    }

    /**
     * Pending check.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'is_cited' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Processing check.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'is_cited' => null,
            'progress_step' => 'Checking citations...',
            'progress_percent' => 50,
            'completed_at' => null,
        ]);
    }

    /**
     * Cited check.
     */
    public function cited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cited' => true,
            'citations' => [
                [
                    'url' => $this->faker->url(),
                    'snippet' => $this->faker->sentence(),
                ],
            ],
        ]);
    }

    /**
     * Not cited check.
     */
    public function notCited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cited' => false,
            'citations' => [],
        ]);
    }

    /**
     * Failed check.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'is_cited' => null,
            'error_message' => 'Failed to check citation.',
            'completed_at' => now(),
        ]);
    }
}
