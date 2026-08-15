<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;
use Database\Factories\ProcedureTaskDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'office_id',
    'procedure_task_id',
    'name',
    'is_required',
    'is_collected',
    'collected_at',
    'file_path',
    'retention_years',
    'retention_until',
])]
class ProcedureTaskDocument extends Model
{
    /** @use HasFactory<ProcedureTaskDocumentFactory> */
    use BelongsToOffice, HasFactory;

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_collected' => 'boolean',
            'collected_at' => 'datetime',
            'retention_until' => 'date',
        ];
    }

    public function procedureTask(): BelongsTo
    {
        return $this->belongsTo(ProcedureTask::class);
    }
}
