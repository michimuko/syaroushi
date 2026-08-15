<?php

namespace Database\Factories;

use App\Enums\RecurrenceType;
use App\Models\ProcedureType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcedureType>
 */
class ProcedureTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'category' => '社会保険',
            'recurrence_type' => RecurrenceType::Yearly,
            'recurrence_rule' => ['month' => 7, 'day' => 10],
            'default_lead_days' => [90, 30, 7],
            'description' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
