<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOffice;
use Database\Factories\OfficeInvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 事務所ごとの月次請求記録（企画書11章）。決済連携は行わず、
 * 「その月いくら請求すべきか」の記録に留める（billing:generate-invoicesバッチが生成）。
 * 一度もStripe決済連携をしていない事務所向けの記録のみが生成されるため、
 * 支払済みかどうか（paid_at）は運営者が個別に入金確認して手動で記録する（未収金管理）。
 */
#[Fillable(['office_id', 'period_start', 'period_end', 'client_count', 'user_count', 'plan_name', 'amount', 'generated_at', 'paid_at'])]
class OfficeInvoice extends Model
{
    /** @use HasFactory<OfficeInvoiceFactory> */
    use BelongsToOffice, HasFactory;

    protected $appends = ['is_paid'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'client_count' => 'integer',
            'user_count' => 'integer',
            'amount' => 'integer',
            'generated_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected function isPaid(): Attribute
    {
        return Attribute::get(fn () => $this->paid_at !== null);
    }
}
