<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Enums\SubscriptionEventType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionEvent>
 */
class SubscriptionEventFactory extends Factory
{
    protected $model = SubscriptionEvent::class;

    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'event_type' => $this->faker->randomElement(SubscriptionEventType::cases()),
            'credit_applied' => $this->faker->numberBetween(-10, 20),
        ];
    }
}
