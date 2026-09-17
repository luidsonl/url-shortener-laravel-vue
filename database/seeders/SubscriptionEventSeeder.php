<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Enums\SubscriptionEventType;

class SubscriptionEventSeeder extends Seeder
{
    public function run(): void
    {
        $subscriptions = Subscription::all();

        if ($subscriptions->isEmpty()) {
            $this->command->warn('No subscriptions found. Please run the Subscription seeder first.');
            return;
        }

        foreach ($subscriptions as $subscription) {
            $eventCount = rand(1, 5);

            for ($i = 0; $i < $eventCount; $i++) {
                SubscriptionEvent::create([
                    'subscription_id' => $subscription->id,
                    'event_type' => $this->getRandomEventType(),
                    'credit_applied' => rand(-5, 15),
                ]);
            }
        }

        $this->command->info('Subscription events seeded successfully.');
    }

    private function getRandomEventType(): SubscriptionEventType
    {
        $types = [
            SubscriptionEventType::CREATED,
            SubscriptionEventType::RENEWED,
            SubscriptionEventType::EXPIRED,
            SubscriptionEventType::CANCELLED,
        ];

        return $types[array_rand($types)];
    }
}
