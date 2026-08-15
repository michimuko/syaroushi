<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\ProcedureTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationLog>
 */
class NotificationLogFactory extends Factory
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
            'recipient_user_id' => User::factory(),
            'channel' => NotificationChannel::Email,
            'lead_days' => 30,
            'sent_at' => now(),
        ];
    }
}
