<?php

namespace Tests\Unit;

use App\Enums\Currency;
use App\Models\Transaction;
use App\Models\SubscriptionEvent;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class TransactionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_that_it_creates_transaction_with_factory()
    {
        $transaction = Transaction::factory()->create();

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertInstanceOf(Currency::class, $transaction->currency);
        $this->assertNotNull($transaction->subscription_event_id);
        $this->assertInstanceOf(Carbon::class, $transaction->processed_at);
        $this->assertIsInt($transaction->amount);
    }

    public function test_that_currency_is_casted_to_enum()
    {
        $transaction = Transaction::factory()->create([
            'currency' => Currency::BRL,
        ]);

        $this->assertInstanceOf(Currency::class, $transaction->currency);
        $this->assertEquals(Currency::BRL, $transaction->currency);
        $this->assertEquals('BRL', $transaction->currency->value);
    }

    public function test_that_processed_at_is_casted_to_datetime()
    {
        $now = now();

        $transaction = Transaction::factory()->create([
            'processed_at' => $now,
        ]);

        $this->assertInstanceOf(Carbon::class, $transaction->processed_at);
        $this->assertEquals($now->format('Y-m-d H:i:s'), $transaction->processed_at->format('Y-m-d H:i:s'));
    }

    public function test_that_it_belongs_to_subscription_event()
    {
        $event = SubscriptionEvent::factory()->create();
        $transaction = Transaction::factory()->for($event)->create();

        $this->assertInstanceOf(SubscriptionEvent::class, $transaction->subscriptionEvent);
        $this->assertEquals($event->id, $transaction->subscriptionEvent->id);
    }

    public function test_that_it_has_correct_fillable_attributes()
    {
        $transaction = new Transaction();

        $this->assertEquals([
            'subscription_event_id',
            'processed_at',
            'amount',
            'currency',
        ], $transaction->getFillable());
    }

    public function test_that_it_has_correct_casts()
    {
        $transaction = new Transaction();
        $casts = $transaction->getCasts();

        $this->assertArrayHasKey('currency', $casts);
        $this->assertArrayHasKey('processed_at', $casts);
        $this->assertEquals(Currency::class, $casts['currency']);
        $this->assertEquals('datetime', $casts['processed_at']);
    }

    public function test_that_it_allows_only_one_transaction_per_subscription_event()
    {
        $event = SubscriptionEvent::factory()->create();

        Transaction::factory()->create([
            'subscription_event_id' => $event->id,
        ]);
        $this->expectException(QueryException::class);

        Transaction::factory()->create([
            'subscription_event_id' => $event->id,
        ]);
    }

    public function test_that_processed_at_is_stored_correctly()
    {
        $date = now()->subDays(3);

        $transaction = Transaction::factory()->create([
            'processed_at' => $date,
        ]);

        $this->assertEquals($date->format('Y-m-d H:i:s'), $transaction->processed_at->format('Y-m-d H:i:s'));
    }

    public function test_that_amount_is_stored_as_integer()
    {
        $transaction = Transaction::factory()->create(['amount' => 2500]);

        $this->assertIsInt($transaction->amount);
        $this->assertEquals(2500, $transaction->amount);
    }
}
