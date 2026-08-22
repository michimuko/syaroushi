<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;
use Database\Factories\ProcedureTaskDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    'retention_notified_at',
])]
#[Hidden(['file_path'])]
class ProcedureTaskDocument extends Model
{
    /** @use HasFactory<ProcedureTaskDocumentFactory> */
    use BelongsToOffice, HasFactory;

    /**
     * file_pathは内部ストレージのキーであり、フロント側は「ファイルが
     * あるかどうか」だけ分かればよいため、真偽値のみをappendsで公開する。
     */
    protected $appends = ['has_file'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_collected' => 'boolean',
            'collected_at' => 'datetime',
            'retention_until' => 'date',
            'retention_notified_at' => 'datetime',
        ];
    }

    protected function hasFile(): Attribute
    {
        return Attribute::make(
            get: fn () => (bool) $this->file_path,
        );
    }

    public function procedureTask(): BelongsTo
    {
        return $this->belongsTo(ProcedureTask::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(DocumentAccessLog::class);
    }
}
