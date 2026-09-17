<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'status' => $this->faker->randomElement(SubscriptionStatus::cases()),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => $this->faker->dateTimeBetween('+1 month', '+1 year'),
        ]);
    }

    /**
     * Indicate that the subscription is canceled.
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::CANCELED,
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::EXPIRED,
            'expires_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Indicate that the subscription is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::PENDING,
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
        ]);
    }

    /**
     * Indicate that the subscription is past due.
     */
    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::PAST_DUE,
            'expires_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Set a specific expiration date.
     */
    public function expiresAt(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $date,
        ]);
    }

    /**
     * Set a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set a specific plan.
     */
    public function forPlan(Plan $plan): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => $plan->id,
        ]);
    }
}