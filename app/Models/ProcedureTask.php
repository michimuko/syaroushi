<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Models\Concerns\BelongsToOffice;
use Database\Factories\ProcedureTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'office_id',
    'client_id',
    'procedure_type_id',
    'title',
    'due_date',
    'original_due_date',
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

    protected $appends = ['is_overdue'];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'original_due_date' => 'date',
            'status' => TaskStatus::class,
            'completed_at' => 'datetime',
            'custom_fields' => 'array',
            'calc_result' => 'array',
        ];
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::get(
            fn () => $this->status !== TaskStatus::Completed && $this->due_date->isPast(),
        );
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

    public function documents(): HasMany
    {
        return $this->hasMany(ProcedureTaskDocument::class);
    }
}
