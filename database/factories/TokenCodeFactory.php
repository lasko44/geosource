<?php

namespace Database\Factories;

use App\Models\TokenCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TokenCode>
 */
class TokenCodeFactory extends Factory
{
    protected $model = TokenCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('????####')),
            'type' => 'promo',
            'tokens' => $this->faker->randomElement([25, 50, 100, 200]),
            'max_uses' => 100,
            'uses_count' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Single-use code.
     */
    public function single(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'single',
            'max_uses' => 1,
        ]);
    }

    /**
     * Expired code.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Inactive code.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Maxed out code.
     */
    public function maxedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses' => 10,
            'uses_count' => 10,
        ]);
    }
}
