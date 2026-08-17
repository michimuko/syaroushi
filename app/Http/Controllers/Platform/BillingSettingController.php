<?php

namespace App\Http\Controllers\Platform;

use App\Enums\BillingCycle;
use App\Http\Controllers\Controller;
use App\Models\BillingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * プラットフォーム全体のトライアル期間・請求サイクルを運営者(platformガード)が変更する。
 * 常に1行のみのシングルトンを編集する。プラン自体の管理はBillingPlanControllerが担う
 * （画面としては同じPlatform/BillingPlans/Indexに統合されている）。
 * OfficeControllerと同じくPolicyは作らず、auth:platformミドルウェアのみで認可する。
 */
class BillingSettingController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'trial_days' => 'required|integer|min:0|max:365',
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
        ]);

        BillingSetting::current()->update($validated);

        return redirect()->route('platform.billing-plans.index')->with('success', '料金設定を更新しました。');
    }
}
