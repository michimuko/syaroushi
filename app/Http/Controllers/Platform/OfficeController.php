<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Module;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\ComputesHighlightPage;
use App\Http\Controllers\Controller;
use App\Models\BillingPlan;
use App\Models\BillingSetting;
use App\Models\Office;
use App\Models\User;
use App\Services\Stripe\StripeSubscriptionGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * 運営者(platformガード)が顧客(office)を管理するコントローラー。
 * Policyは作らず、routes/platform.phpのauth:platformミドルウェアのみで
 * 認可する（運営者にロール階層はなく、認証済みなら全操作可でよいため）。
 */
class OfficeController extends Controller
{
    use ComputesHighlightPage;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'attention_only' => 'nullable|boolean',
        ]);

        $offices = Office::query()
            ->withCount('users')
            ->with('billingPlan')
            ->when(
                $validated['search'] ?? null,
                fn ($query, $search) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('office_code', 'like', "%{$search}%")
                ),
            )
            ->when(
                $validated['attention_only'] ?? false,
                fn ($query) => $query->needsBillingAttention(),
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Offices/Index', [
            'offices' => $offices,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'attention_only' => $validated['attention_only'] ?? false,
            ],
            'attentionCount' => Office::query()->needsBillingAttention()->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Platform/Offices/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * 事務所の新規契約＝office新規作成＋最初のownerユーザー作成を1トランザクションで行う
     * （RegisteredUserController@storeと同じ思想）。
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'office_name' => 'required|string|max:255',
            'office_code' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:offices,office_code'],
            'owner_name' => 'required|string|max:255',
            // owner_login_idは事務所内でのみ一意であればよく、新規作成する事務所には
            // まだ他のユーザーがいないため、ここでの重複チェックは不要。
            'owner_login_id' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'owner_email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'owner_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $office = DB::transaction(function () use ($validated) {
            $office = Office::create([
                'name' => $validated['office_name'],
                'office_code' => Str::lower($validated['office_code']),
                'trial_ends_at' => now()->addDays(BillingSetting::current()->trial_days),
            ]);

            // office_idはOffice::create()直後の信頼できる値。AssignsOfficeOnCreateの
            // 「認証中のwebガードユーザーの事務所に強制」フックが割り込むと、運営者が同じ
            // ブラウザで別事務所のwebガードにもログインしていた場合に書き換わってしまうため止める。
            User::withoutEvents(fn () => User::create([
                'office_id' => $office->id,
                'name' => $validated['owner_name'],
                'login_id' => $validated['owner_login_id'],
                'email' => $validated['owner_email'],
                'password' => Hash::make($validated['owner_password']),
                'role' => UserRole::Owner,
            ]));

            return $office;
        });

        $page = $this->pageContainingId(Office::query()->orderBy('name'), $office->id);

        return redirect()->route('platform.offices.index', $page > 1 ? ['page' => $page] : [])
            ->with('success', '事務所を作成しました。')
            ->with('highlightId', $office->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Office $office): Response
    {
        $office->loadMissing('billingPlan');

        return Inertia::render('Platform/Offices/Edit', [
            'office' => $office,
            'availableModules' => array_map(
                fn (Module $module) => ['value' => $module->value, 'label' => $module->label()],
                Module::cases(),
            ),
            'assignableBillingPlans' => BillingPlan::query()
                ->where(function ($query) use ($office) {
                    $query->where('is_active', true);
                    if ($office->billing_plan_id !== null) {
                        $query->orWhere('id', $office->billing_plan_id);
                    }
                })
                ->orderBy('sort_order')
                ->get(),
            'usage' => [
                'clientCount' => $office->billableClientCount(),
                'userCount' => $office->currentUserCount(),
            ],
            'exceededLimits' => $office->exceedsPlanLimits(),
            'canConfirmBilling' => $office->hasActiveStripeSubscription(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Office $office): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'office_code' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', Rule::unique('offices', 'office_code')->ignore($office->id)],
            'is_active' => 'required|boolean',
            'trial_ends_at' => 'nullable|date',
            'enabled_modules' => 'nullable|array',
            'enabled_modules.*' => [Rule::enum(Module::class)],
            'billing_plan_id' => 'nullable|exists:billing_plans,id',
            'custom_monthly_price' => 'nullable|integer|min:0',
        ]);

        $validated['office_code'] = Str::lower($validated['office_code']);

        $office->update($validated);

        $page = $this->pageContainingId(Office::query()->orderBy('name'), $office->id);

        return redirect()->route('platform.offices.index', $page > 1 ? ['page' => $page] : [])
            ->with('success', '事務所情報を更新しました。')
            ->with('highlightId', $office->id);
    }

    /**
     * 「請求確定」：現在設定されている金額（個別値引きがあればそれ、無ければプラン標準額）を
     * Stripeのサブスクリプション価格に同期し、差額をその場でプロレーション請求する。
     * Stripeで定期契約が始まっていない事務所（未契約・トライアル中）は対象外
     * （月次請求バッチ側でDB上の請求記録を作る運用のまま）。
     */
    public function syncBilling(Office $office, StripeSubscriptionGateway $gateway): RedirectResponse
    {
        $office->loadMissing('billingPlan');

        if (! $office->hasActiveStripeSubscription() || $office->stripe_subscription_id === null) {
            return back()->with('error', 'この事務所はStripeで定期契約されていないため、請求確定はできません。');
        }

        $amount = $office->custom_monthly_price ?? $office->billingPlan?->monthly_price;

        if ($amount === null) {
            return back()->with('error', '請求金額を決定できません。料金プランまたは個別価格を設定してください。');
        }

        $priceId = $office->custom_monthly_price === null ? $office->billingPlan?->stripe_price_id : null;

        if ($priceId === null && $office->custom_monthly_price === null) {
            return back()->with('error', '割り当てられているプランにStripeの価格が設定されていません。');
        }

        $productId = null;
        if ($priceId === null && $office->billingPlan?->stripe_price_id !== null) {
            $productId = $gateway->productIdForPrice($office->billingPlan->stripe_price_id);
        }

        try {
            $result = $gateway->syncSubscriptionPrice(
                subscriptionId: $office->stripe_subscription_id,
                priceId: $priceId,
                unitAmountYen: $priceId === null ? $amount : null,
                productId: $productId,
                productFallbackName: $office->name.' - '.($office->billingPlan?->name ?? 'カスタムプラン'),
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Stripeへの反映に失敗しました：'.$e->getMessage());
        }

        $formattedAmount = number_format($amount);

        if ($result['invoice_status'] === 'paid') {
            return back()->with('success', "設定金額（¥{$formattedAmount}/月）をStripeの請求に反映し、即時決済しました。");
        }

        return back()->with(
            'error',
            "Stripeの請求には反映しましたが、即時決済は完了していません（請求書ステータス：{$result['invoice_status']}）。支払い方法をご確認ください。",
        );
    }
}
