<?php

namespace App\Http\Controllers\Platform;

use App\Enums\BillingCycle;
use App\Http\Controllers\Controller;
use App\Models\BillingSetting;
use App\Models\Office;
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

        $setting = BillingSetting::current();
        $trialDaysChanged = $setting->trial_days !== $validated['trial_days'];

        $setting->update($validated);

        // trial_daysは新規契約時にしか読まれないため、変更しただけでは既存事務所のトライアル
        // 終了日に反映されない。まだ課金が始まっていない（Stripeで定期契約済みでない）
        // トライアル中の事務所だけは、契約日(created_at)を基準に再計算して合わせる。
        // 既にStripeで契約済みの事務所や、トライアルが既に終わっている事務所は対象外
        // （後者は運営者が個別に判断して手動でtrial_ends_atを編集する運用のまま）。
        $updatedOfficeCount = 0;
        if ($trialDaysChanged) {
            $updatedOfficeCount = Office::all()
                ->filter(fn (Office $office) => $office->isTrialActive() && ! $office->hasActiveStripeSubscription())
                ->each(fn (Office $office) => $office->update([
                    'trial_ends_at' => $office->created_at->copy()->addDays($validated['trial_days']),
                ]))
                ->count();
        }

        $message = '料金設定を更新しました。';
        if ($updatedOfficeCount > 0) {
            $message .= "（トライアル中の{$updatedOfficeCount}事務所のトライアル終了日も再計算しました）";
        }

        return redirect()->route('platform.billing-plans.index')->with('success', $message);
    }
}
