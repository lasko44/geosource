<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->randomNumber(4),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Team $team) {
            // Automatically add the owner as a member with 'owner' role
            $team->members()->attach($team->owner_id, ['role' => 'owner']);
        });
    }

    /**
     * Team with white label settings.
     */
    public function withWhiteLabel(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_name' => $this->faker->company(),
            'primary_color' => $this->faker->hexColor(),
            'secondary_color' => $this->faker->hexColor(),
            'contact_email' => $this->faker->email(),
            'website_url' => $this->faker->url(),
        ]);
    }

    /**
     * Team with description.
     */
    public function withDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $this->faker->paragraph(),
        ]);
    }
}
