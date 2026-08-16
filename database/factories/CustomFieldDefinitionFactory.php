<?php

namespace Database\Factories;

use App\Enums\CustomFieldTarget;
use App\Enums\CustomFieldType;
use App\Models\CustomFieldDefinition;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomFieldDefinition>
 */
class CustomFieldDefinitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'office_id' => Office::factory(),
            'target' => CustomFieldTarget::Client,
            'label' => $this->faker->words(2, true),
            'field_type' => CustomFieldType::Text,
            'options' => null,
        ];
    }

    public function select(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => CustomFieldType::Select,
            'options' => ['A', 'B', 'C'],
        ]);
    }

    public function forProcedureTask(): static
    {
        return $this->state(fn (array $attributes) => [
            'target' => CustomFieldTarget::ProcedureTask,
        ]);
    }
}
