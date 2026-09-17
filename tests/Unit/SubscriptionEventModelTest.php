<?php

namespace Tests\Unit;

use App\Enums\SubscriptionEventType;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionEventModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_subscription_event_with_factory()
    {
        $event = SubscriptionEvent::factory()->create();

        $this->assertInstanceOf(SubscriptionEvent::class, $event);
        $this->assertInstanceOf(SubscriptionEventType::class, $event->event_type);
        $this->assertIsInt($event->credit_applied);
        $this->assertNotNull($event->subscription_id);
    }

    public function test_event_type_is_casted_to_enum()
    {
        $event = SubscriptionEvent::factory()->create([
            'event_type' => SubscriptionEventType::CREATED,
        ]);

        $this->assertInstanceOf(SubscriptionEventType::class, $event->event_type);
        $this->assertEquals(SubscriptionEventType::CREATED, $event->event_type);
        $this->assertEquals('created', $event->event_type->value);
    }

    public function test_credit_applied_is_casted_to_integer()
    {
        $event = SubscriptionEvent::factory()->create([
            'credit_applied' => 10,
        ]);

        $this->assertIsInt($event->credit_applied);
        $this->assertEquals(10, $event->credit_applied);
    }

    public function test_that_it_belongs_to_subscription()
    {
        $subscription = Subscription::factory()->create();
        $event = SubscriptionEvent::factory()->for($subscription)->create();

        $this->assertInstanceOf(Subscription::class, $event->subscription);
        $this->assertEquals($subscription->id, $event->subscription->id);
    }

    public function test_it_handles_all_enum_event_types()
    {
        $types = SubscriptionEventType::cases();

        foreach ($types as $type) {
            $event = SubscriptionEvent::factory()->create([
                'event_type' => $type,
            ]);

            $this->assertEquals($type, $event->event_type);
            $this->assertEquals($type->value, $event->event_type->value);
        }
    }

    public function test_fillable_attributes()
    {
        $event = new SubscriptionEvent();

        $this->assertEquals([
            'subscription_id',
            'event_type',
            'credit_applied',
        ], $event->getFillable());
    }

    public function test_casts_attributes()
    {
        $event = new SubscriptionEvent();
        $casts = $event->getCasts();

        $this->assertArrayHasKey('credit_applied', $casts);
        $this->assertArrayHasKey('event_type', $casts);
        $this->assertEquals('integer', $casts['credit_applied']);
        $this->assertEquals(SubscriptionEventType::class, $casts['event_type']);
    }
}
