<?php

namespace App\Http\Controllers\Platform;

use App\Enums\BillingCycle;
use App\Http\Controllers\Controller;
use App\Models\BillingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * プラットフォーム全体の料金設定（企画書11章：顧問先数に応じた従量制）を
 * 運営者(platformガード)が変更する画面。常に1行のみのシングルトンを編集する。
 * OfficeControllerと同じくPolicyは作らず、auth:platformミドルウェアのみで認可する。
 */
class BillingSettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Platform/BillingSettings/Edit', [
            'billingSetting' => BillingSetting::current(),
            'billingCycles' => array_map(
                fn (BillingCycle $case) => ['value' => $case->value, 'label' => $case->label()],
                BillingCycle::cases(),
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'unit_price_per_client' => 'required|integer|min:0|max:1000000',
            'trial_days' => 'required|integer|min:0|max:365',
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
        ]);

        BillingSetting::current()->update($validated);

        return redirect()->route('platform.billing-settings.edit')->with('success', '料金設定を更新しました。');
    }
}
