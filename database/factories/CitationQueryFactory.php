<?php

namespace Database\Factories;

use App\Models\CitationQuery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CitationQuery>
 */
class CitationQueryFactory extends Factory
{
    protected $model = CitationQuery::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'query' => $this->faker->sentence(),
            'domain' => $this->faker->domainName(),
            'brand' => $this->faker->company(),
            'frequency' => 'manual',
            'is_active' => true,
        ];
    }

    /**
     * Daily frequency.
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'daily',
        ]);
    }

    /**
     * Weekly frequency.
     */
    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'weekly',
        ]);
    }

    /**
     * Inactive query.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
