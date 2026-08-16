<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;
use Database\Factories\OfficeInvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 事務所ごとの月次請求記録（企画書11章）。決済連携は行わず、
 * 「その月いくら請求すべきか」の記録に留める（billing:generate-invoicesバッチが生成）。
 */
#[Fillable(['office_id', 'period_start', 'period_end', 'client_count', 'unit_price', 'amount', 'generated_at'])]
class OfficeInvoice extends Model
{
    /** @use HasFactory<OfficeInvoiceFactory> */
    use BelongsToOffice, HasFactory;

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'client_count' => 'integer',
            'unit_price' => 'integer',
            'amount' => 'integer',
            'generated_at' => 'datetime',
        ];
    }
}
