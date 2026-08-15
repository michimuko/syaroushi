<?php

namespace Database\Factories;

use App\Models\ProcedureTask;
use App\Models\ProcedureTaskDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcedureTaskDocument>
 */
class ProcedureTaskDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'procedure_task_id' => ProcedureTask::factory(),
            'name' => $this->faker->words(2, true),
            'is_required' => true,
            'is_collected' => false,
            'collected_at' => null,
            'file_path' => null,
            'retention_years' => null,
            'retention_until' => null,
        ];
    }

    public function collected(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_collected' => true,
            'collected_at' => now(),
            'file_path' => 'procedure-task-documents/'.fake()->uuid().'.pdf',
        ]);
    }
}
