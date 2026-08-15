<?php

namespace Database\Factories;

use App\Enums\DocumentAccessAction;
use App\Models\DocumentAccessLog;
use App\Models\ProcedureTaskDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAccessLog>
 */
class DocumentAccessLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'procedure_task_document_id' => ProcedureTaskDocument::factory(),
            'user_id' => User::factory(),
            'action' => DocumentAccessAction::Download,
            'accessed_at' => now(),
        ];
    }
}
