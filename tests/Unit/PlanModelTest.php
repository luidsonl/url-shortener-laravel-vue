<?php

namespace Tests\Unit;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;

class PlanModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_plan_with_factory()
    {
        $plan = Plan::factory()->create();

        $this->assertInstanceOf(Plan::class, $plan);
        $this->assertNotEmpty($plan->name);
    }

    public function test_it_stores_prices_as_integers()
    {
        $plan = Plan::factory()->create([
            'price_monthly' => 1999,
            'price_yearly' => 19999,
        ]);

        $this->assertEquals(1999, $plan->price_monthly);
        $this->assertEquals(19999, $plan->price_yearly);
    }

    public function test_price_accessors_convert_to_decimal()
    {
        $plan = Plan::factory()->create([
            'price_monthly' => 1999,
            'price_yearly' => 19999,
        ]);

        $this->assertEquals(19.99, $plan->monthly_price);
        $this->assertEquals(199.99, $plan->yearly_price);
    }

    public function test_it_creates_inactive_plan()
    {
        $plan = Plan::factory()->inactive()->create();

        $this->assertFalse($plan->is_active);
    }
}
