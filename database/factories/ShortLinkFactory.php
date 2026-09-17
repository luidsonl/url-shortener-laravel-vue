<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShortLink>
 */
class ShortLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_url' => $this->faker->url(),
            'code' => null,
            'clicks' => $this->faker->numberBetween(0, 1000),
            'expires_at' => $this->faker->optional(0.3, null)
                ->dateTimeBetween('+1 week', '+1 year'),
        ];
    }

    /**
     * Indicate that the short link belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the short link has many clicks.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicks' => $this->faker->numberBetween(1000, 10000),
        ]);
    }

    /**
     * Indicate that the short link is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    /**
     * Indicate that the short link never expires.
     */
    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }

    /**
     * Indicate that the short link has a specific code.
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
        ]);
    }
}