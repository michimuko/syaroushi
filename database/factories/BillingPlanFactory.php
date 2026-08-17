<?php

namespace Database\Factories;

use App\Models\BillingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingPlan>
 */
class BillingPlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'テストプラン'.$this->faker->unique()->numberBetween(1, 100000),
            'max_clients' => $this->faker->numberBetween(10, 100),
            'max_users' => $this->faker->numberBetween(3, 20),
            'monthly_price' => $this->faker->numberBetween(5000, 30000),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
