<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BoostPlan;

class BoostPlanSeeder extends Seeder
{
    /**
     * Seed the application's boost plans.
     */
    public function run(): void
    {
        $plans = [
            [
                'name'        => 'Basic',
                'days'        => 3,
                'price_usd'   => 4.99,
                'description' => "Good for short promos\nHighlighted for 3 days",
                'is_active'   => 1,
            ],
            [
                'name'        => 'Standard',
                'days'        => 7,
                'price_usd'   => 9.99,
                'description' => "1 week of priority placement\nGreat value",
                'is_active'   => 1,
            ],
            [
                'name'        => 'Pro',
                'days'        => 14,
                'price_usd'   => 17.99,
                'description' => "2 weeks exposure\nRecommended for new products",
                'is_active'   => 1,
            ],
            [
                'name'        => 'Premium',
                'days'        => 30,
                'price_usd'   => 34.99,
                'description' => "Full month boost\nMaximum reach",
                'is_active'   => 1,
            ],
        ];

        foreach ($plans as $plan) {
            BoostPlan::updateOrCreate(
                ['name' => $plan['name']], // unique key to avoid duplicates on re-seed
                $plan
            );
        }
    }
}
