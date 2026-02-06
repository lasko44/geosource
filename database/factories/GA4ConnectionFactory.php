<?php

namespace Database\Factories;

use App\Models\GA4Connection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GA4Connection>
 */
class GA4ConnectionFactory extends Factory
{
    protected $model = GA4Connection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'google_account_id' => $this->faker->numerify('############'),
            'property_id' => $this->faker->numerify('#########'),
            'property_name' => $this->faker->company().' Analytics',
            'access_token' => $this->faker->sha256(),
            'refresh_token' => $this->faker->sha256(),
            'token_expires_at' => now()->addHour(),
            'is_active' => true,
            'sync_status' => GA4Connection::SYNC_STATUS_IDLE,
        ];
    }

    /**
     * Indicate that the connection is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the token is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'token_expires_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the connection is syncing.
     */
    public function syncing(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => GA4Connection::SYNC_STATUS_SYNCING,
        ]);
    }

    /**
     * Indicate that the sync has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => GA4Connection::SYNC_STATUS_FAILED,
            'sync_error' => 'Failed to sync data from Google Analytics.',
        ]);
    }
}
