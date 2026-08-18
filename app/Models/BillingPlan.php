<?php

namespace App\Models;

use Database\Factories\BillingPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 運営者が管理する料金プランのカタログ（顧問先数上限×ユーザー数上限×月額の組み合わせ）。
 * max_clients/max_usersがnullは無制限、monthly_priceがnullは個別見積りを意味する。
 */
#[Fillable(['name', 'max_clients', 'max_users', 'monthly_price', 'stripe_price_id', 'sort_order', 'is_active'])]
class BillingPlan extends Model
{
    /** @use HasFactory<BillingPlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'max_clients' => 'integer',
            'max_users' => 'integer',
            'monthly_price' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }
}
