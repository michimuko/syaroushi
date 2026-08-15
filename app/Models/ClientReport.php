<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;
use Database\Factories\ClientReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 顧問先向け進捗レポート（PDF）の生成履歴（企画書5-B・7章）。
 * 生成のみ・更新は行わない。
 */
#[Fillable([
    'office_id',
    'client_id',
    'generated_by',
    'period_start',
    'period_end',
    'comment',
    'pdf_path',
])]
class ClientReport extends Model
{
    /** @use HasFactory<ClientReportFactory> */
    use BelongsToOffice, HasFactory;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
