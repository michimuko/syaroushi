<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Models\Concerns\BelongsToOffice;
use Database\Factories\ProcedureTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'office_id',
    'client_id',
    'procedure_type_id',
    'title',
    'due_date',
    'status',
    'assigned_user_id',
    'completed_at',
    'notes',
    'custom_fields',
    'calc_result',
])]
class ProcedureTask extends Model
{
    /** @use HasFactory<ProcedureTaskFactory> */
    use BelongsToOffice, HasFactory;

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'status' => TaskStatus::class,
            'completed_at' => 'datetime',
            'custom_fields' => 'array',
            'calc_result' => 'array',
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

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
