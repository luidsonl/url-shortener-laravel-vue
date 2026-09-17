<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\SubscriptionEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_event_id' => SubscriptionEvent::factory(),
            'processed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'amount' => $this->faker->numberBetween(500, 10000),
            'currency' => $this->faker->randomElement(Currency::cases()),
        ];
    }

    public function unprocessed(): static
    {
        return $this->state(fn () => [
            'processed_at' => null,
        ]);
    }
}
