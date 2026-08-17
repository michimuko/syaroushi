<?php

namespace App\Models;

use App\Enums\BillingCycle;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * プラットフォーム全体の料金設定（企画書11章）。常に1行のみ存在するシングルトン。
 * 初期値はマイグレーションで投入済み。運営者(platformガード)のみが変更できる。
 */
#[Fillable(['trial_days', 'billing_cycle'])]
class BillingSetting extends Model
{
    protected function casts(): array
    {
        return [
            'trial_days' => 'integer',
            'billing_cycle' => BillingCycle::class,
        ];
    }

    /**
     * シングルトン行を取得する。マイグレーションで必ず1行投入されるが、
     * 万一存在しない場合に備えて既定値でのフォールバック作成も行う。
     */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'trial_days' => 30,
            'billing_cycle' => BillingCycle::Monthly,
        ]);
    }
}
