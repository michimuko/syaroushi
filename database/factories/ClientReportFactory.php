<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientReport;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientReport>
 */
class ClientReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $office = Office::factory()->create();

        return [
            'office_id' => $office->id,
            'client_id' => Client::factory()->for($office),
            'generated_by' => User::factory()->for($office),
            'period_start' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
            'period_end' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'comment' => null,
            'pdf_path' => 'client-reports/'.$this->faker->uuid().'.pdf',
            'created_at' => now(),
        ];
    }
}
