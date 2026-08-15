<?php

namespace App\Models;

use App\Enums\DocumentAccessAction;
use App\Models\Concerns\BelongsToOffice;
use Database\Factories\DocumentAccessLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * procedure_task_documents（特定個人情報を含み得る書類）の閲覧・ダウンロード記録
 * （企画書7.7章：「取扱状況の把握」要件対応）。作成のみ、更新・削除は行わない。
 */
#[Fillable([
    'office_id',
    'procedure_task_document_id',
    'user_id',
    'action',
    'accessed_at',
])]
class DocumentAccessLog extends Model
{
    /** @use HasFactory<DocumentAccessLogFactory> */
    use BelongsToOffice, HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'action' => DocumentAccessAction::class,
            'accessed_at' => 'datetime',
        ];
    }

    public function procedureTaskDocument(): BelongsTo
    {
        return $this->belongsTo(ProcedureTaskDocument::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
