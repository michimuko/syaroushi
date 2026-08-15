<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientProcedureSubscription;
use App\Models\ProcedureType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientProcedureSubscription>
 */
class ClientProcedureSubscriptionFactory extends Factory
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
            'is_active' => true,
            'lead_days_override' => null,
        ];
    }
}
