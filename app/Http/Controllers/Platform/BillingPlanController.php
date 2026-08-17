<?php

namespace App\Http\Controllers\Platform;

use App\Enums\BillingCycle;
use App\Http\Controllers\Controller;
use App\Models\BillingPlan;
use App\Models\BillingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 運営者が料金プランのカタログ（顧問先数上限×ユーザー数上限×月額）を管理する画面。
 * 一覧表示自体がそのまま「料金表」のプレビューを兼ねる。過去請求はplan_nameを
 * 文字列でスナップショットしているため、プランは削除せずis_activeで無効化する運用にする。
 */
class BillingPlanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Platform/BillingPlans/Index', [
            'billingPlans' => BillingPlan::query()->orderBy('sort_order')->orderBy('id')->get(),
            'billingSetting' => BillingSetting::current(),
            'billingCycles' => array_map(
                fn (BillingCycle $case) => ['value' => $case->value, 'label' => $case->label()],
                BillingCycle::cases(),
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        BillingPlan::create($validated);

        return redirect()->route('platform.billing-plans.index')->with('success', 'プランを追加しました。');
    }

    public function update(Request $request, BillingPlan $billing_plan): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $billing_plan->update($validated);

        return redirect()->route('platform.billing-plans.index')->with('success', 'プランを更新しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'max_clients' => 'nullable|integer|min:1|max:100000',
            'max_users' => 'nullable|integer|min:1|max:100000',
            'monthly_price' => 'nullable|integer|min:0|max:10000000',
            'sort_order' => 'required|integer|min:0|max:1000',
            'is_active' => 'required|boolean',
        ];
    }
}
