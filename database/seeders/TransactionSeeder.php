<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\SubscriptionEvent;
use App\Enums\Currency;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $events = SubscriptionEvent::with('subscription.plan')->get();

        if ($events->isEmpty()) {
            $this->command->warn('No subscription events found. Please run the SubscriptionEvent seeder first.');
            return;
        }

        $createdCount = 0;

        foreach ($events as $event) {
            if ($event->transaction()->exists()) {
                continue;
            }

            $subscription = $event->subscription;
            if (! $subscription || ! $subscription->plan) {
                $this->command->warn("Skipping event {$event->id}: missing subscription or plan.");
                continue;
            }

            $plan = $subscription->plan;

            $amount = rand(0, 100) < 70 ? $plan->price_monthly : $plan->price_yearly;

            Transaction::create([
                'subscription_event_id' => $event->id,
                'processed_at' => Carbon::now()->subDays(rand(0, 30)),
                'amount' => $amount,
                'currency' => Currency::BRL,
            ]);

            $createdCount++;
        }

        $this->command->info("{$createdCount} transactions created successfully based on plan prices.");
    }
}
