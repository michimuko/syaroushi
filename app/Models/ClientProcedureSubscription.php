<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;
use Database\Factories\ClientProcedureSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['office_id', 'client_id', 'procedure_type_id', 'is_active', 'lead_days_override'])]
class ClientProcedureSubscription extends Model
{
    /** @use HasFactory<ClientProcedureSubscriptionFactory> */
    use BelongsToOffice, HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'lead_days_override' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function procedureType(): BelongsTo
    {
        return $this->belongsTo(ProcedureType::class);
    }
}
