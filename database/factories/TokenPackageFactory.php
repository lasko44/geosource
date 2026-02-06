<?php

namespace Database\Factories;

use App\Models\TokenPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TokenPackage>
 */
class TokenPackageFactory extends Factory
{
    protected $model = TokenPackage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tokens = $this->faker->randomElement([50, 100, 200, 500, 1000]);
        $pricePerToken = $this->faker->randomFloat(2, 0.05, 0.15);

        return [
            'name' => $tokens.' Tokens',
            'tokens' => $tokens,
            'price_cents' => (int) ($tokens * $pricePerToken * 100),
            'stripe_price_id' => 'price_'.$this->faker->uuid(),
            'savings_percent' => $tokens > 100 ? $this->faker->numberBetween(5, 25) : 0,
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Mark package as popular.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_popular' => true,
        ]);
    }

    /**
     * Mark package as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
