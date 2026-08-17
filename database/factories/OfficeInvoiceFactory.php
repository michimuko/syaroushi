<?php

namespace Database\Factories;

use App\Models\OfficeInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficeInvoice>
 */
class OfficeInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = $this->faker->dateTimeBetween('-6 months', 'now')->modify('first day of this month');
        $periodEnd = (clone $periodStart)->modify('last day of this month');

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'client_count' => $this->faker->numberBetween(1, 30),
            'user_count' => $this->faker->numberBetween(1, 10),
            'plan_name' => 'スタンダード',
            'amount' => 14800,
            'generated_at' => now(),
        ];
    }
}
