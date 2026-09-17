<?php

namespace Tests\Unit;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_that_it_creates_subscription_with_factory()
    {
        $subscription = Subscription::factory()->create();

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertInstanceOf(SubscriptionStatus::class, $subscription->status);
        $this->assertNotNull($subscription->expires_at);
        $this->assertNotNull($subscription->user_id);
        $this->assertNotNull($subscription->plan_id);
    }

    public function test_that_it_stores_timestamps_correctly()
    {
        $expiresAt = now()->addYear();
        
        $subscription = Subscription::factory()->create([
            'expires_at' => $expiresAt,
        ]);

        $this->assertEquals($expiresAt->format('Y-m-d H:i:s'), $subscription->expires_at->format('Y-m-d H:i:s'));
    }

    public function test_status_is_casted_to_enum()
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $this->assertInstanceOf(SubscriptionStatus::class, $subscription->status);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertEquals('active', $subscription->status->value);
    }

    public function test_that_it_creates_active_subscription()
    {
        $subscription = Subscription::factory()->active()->create();

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertTrue($subscription->expires_at->isFuture());
    }

    public function test_that_it_creates_expired_subscription()
    {
        $subscription = Subscription::factory()->expired()->create();

        $this->assertEquals(SubscriptionStatus::EXPIRED, $subscription->status);
        $this->assertTrue($subscription->expires_at->isPast());
    }

    public function test_that_it_creates_canceled_subscription()
    {
        $subscription = Subscription::factory()->canceled()->create();

        $this->assertEquals(SubscriptionStatus::CANCELED, $subscription->status);
    }

    public function test_that_it_creates_pending_subscription()
    {
        $subscription = Subscription::factory()->pending()->create();

        $this->assertEquals(SubscriptionStatus::PENDING, $subscription->status);
    }

    public function test_that_it_creates_past_due_subscription()
    {
        $subscription = Subscription::factory()->pastDue()->create();

        $this->assertEquals(SubscriptionStatus::PAST_DUE, $subscription->status);
        $this->assertTrue($subscription->expires_at->isPast() || $subscription->expires_at->isToday());
    }

    public function test_that_it_belongs_to_user()
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->forUser($user)->create();

        $this->assertInstanceOf(User::class, $subscription->user);
        $this->assertEquals($user->id, $subscription->user->id);
    }

    public function test_that_it_belongs_to_plan()
    {
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()->forPlan($plan)->create();

        $this->assertInstanceOf(Plan::class, $subscription->plan);
        $this->assertEquals($plan->id, $subscription->plan->id);
    }

    public function test_that_it_creates_subscription_with_specific_expiration_date()
    {
        $expirationDate = now()->addMonths(6);
        
        $subscription = Subscription::factory()
            ->expiresAt($expirationDate)
            ->create();

        $this->assertEquals($expirationDate->format('Y-m-d H:i:s'), $subscription->expires_at->format('Y-m-d H:i:s'));
    }

    public function test_that_it_handles_all_enum_statuses()
    {
        $statuses = SubscriptionStatus::cases();
        
        foreach ($statuses as $status) {
            $subscription = Subscription::factory()->create([
                'status' => $status,
            ]);

            $this->assertEquals($status, $subscription->status);
            $this->assertEquals($status->value, $subscription->status->value);
        }
    }

    public function test_fillable_attributes()
    {
        $subscription = new Subscription();

        $this->assertEquals([
            'user_id',
            'plan_id',
            'status',
            'expires_at',
        ], $subscription->getFillable());
    }

    public function test_casts_attributes()
    {
        $subscription = new Subscription();
        $casts = $subscription->getCasts();

        $this->assertArrayHasKey('status', $casts);
        $this->assertArrayHasKey('expires_at', $casts);
        $this->assertEquals(SubscriptionStatus::class, $casts['status']);
        $this->assertEquals('datetime', $casts['expires_at']);
    }
}