<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcedureTask>
 */
class ProcedureTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'procedure_type_id' => ProcedureType::factory(),
            'title' => $this->faker->words(3, true),
            'due_date' => $this->faker->dateTimeBetween('now', '+90 days'),
            'status' => TaskStatus::NotStarted,
            'assigned_user_id' => null,
            'completed_at' => null,
            'notes' => null,
            'custom_fields' => null,
            'calc_result' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->subDays(3),
            'status' => TaskStatus::InProgress,
        ]);
    }
}
