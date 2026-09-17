<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'price_monthly' => 499,
                'price_yearly' => 4990,
                'description' => 'Ideal for individuals starting out.',
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'price_monthly' => 1999,
                'price_yearly' => 19990,
                'description' => 'Perfect for small teams and professionals.',
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'price_monthly' => 4999,
                'price_yearly' => 49990,
                'description' => 'Best for companies with large-scale needs.',
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
