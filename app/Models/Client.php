<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Models\Concerns\BelongsToOffice;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'office_id',
    'name',
    'representative_name',
    'address',
    'phone',
    'email',
    'contract_start_date',
    'status',
    'assigned_user_id',
    'notes',
    'custom_fields',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToOffice, HasFactory;

    protected function casts(): array
    {
        return [
            'contract_start_date' => 'date',
            'status' => ClientStatus::class,
            'custom_fields' => 'array',
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
