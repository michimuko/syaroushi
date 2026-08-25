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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
            'trashed' => 'nullable|boolean',
        ]);
        // 'boolean'バリデーションルールは値の妥当性チェックのみで型変換はしないため
        // （クエリ文字列由来だと"1"のような文字列のまま残る）、$request->boolean()で明示的にcastする。
        $trashed = $request->boolean('trashed');
        $attentionOnly = $request->boolean('attention_only');

        $offices = Office::query()
            ->when($trashed, fn ($query) => $query->onlyTrashed())
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
                ! $trashed && $attentionOnly,
                fn ($query) => $query->needsBillingAttention(),
            )
            ->orderBy($trashed ? 'deleted_at' : 'name', $trashed ? 'desc' : 'asc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Offices/Index', [
            'offices' => $offices,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'attention_only' => $attentionOnly,
                'trashed' => $trashed,
            ],
            'attentionCount' => Office::query()->needsBillingAttention()->count(),
            'physicalPurgeAfterDays' => Office::PHYSICAL_PURGE_AFTER_SOFT_DELETE_DAYS,
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
            'canDelete' => $office->isEligibleForDeletion(),
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

        // trial_ends_atを個別に延長・変更した場合、データ削除ポリシーの通知既読フラグが
        // 古いスケジュール基準のまま残ると、再度トライアルが切れても通知が二度と飛ばなくなる
        // ため、変更時はリセットする。
        $trialEndsAtChanged = ($validated['trial_ends_at'] ?? null) !== $office->trial_ends_at?->toDateString();
        if ($trialEndsAtChanged) {
            $validated['trial_ended_notified_at'] = null;
            $validated['deletion_warning_notified_at'] = null;
            $validated['deletion_final_notice_notified_at'] = null;
        }

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

    /**
     * 未払い放置時のデータ削除ポリシーに基づくソフト削除。バグによる誤削除を防ぐため、
     * 対象条件(isEligibleForDeletion())をサーバー側でも再検証してから実行する。
     * ソフト削除後は事務所の全ユーザーがログインできなくなる（EnsureOfficeIsUsable参照）。
     */
    public function softDelete(Office $office): RedirectResponse
    {
        if (! $office->isEligibleForDeletion()) {
            return back()->with('error', 'この事務所はまだ削除対象条件を満たしていません。');
        }

        $office->delete();

        return redirect()->route('platform.offices.index')
            ->with('success', "事務所「{$office->name}」をソフト削除しました。30日間は復元できます。");
    }

    /**
     * ソフト削除の取り消し。物理削除（forceDelete）済みの場合はレコード自体が
     * 存在しないため、Route::withTrashed()経由でも404になり自然にブロックされる。
     */
    public function restore(Office $office): RedirectResponse
    {
        $office->restore();

        return back()->with('success', "事務所「{$office->name}」を復元しました。");
    }

    /**
     * 物理削除。ソフト削除から30日以上経過した事務所のみ、運営者の手動確認で実行する
     * （自動バッチでは行わない）。office_invoicesが残っている場合は財務記録保護のため拒否する
     * （trial_ends_at未設定の運営者による手動請求先はそもそもisEligibleForDeletionの対象外だが、
     * 念のための安全弁）。procedure_task_documents・client_reportsのS3実体を先に削除してから
     * forceDelete()で実DELETE文を発行し、他の全テナントテーブルはoffice_idのcascadeOnDeleteで
     * 一括削除される。Officeの行自体もこの時点で完全に消える（監査目的でLogに記録を残す）。
     */
    public function purge(Office $office): RedirectResponse
    {
        if (! $office->isEligibleForPhysicalPurge()) {
            return back()->with('error', 'この事務所はまだ物理削除の対象条件（ソフト削除から30日経過）を満たしていません。');
        }

        if ($office->invoices()->exists()) {
            return back()->with('error', '請求記録が残っているため物理削除を拒否しました。財務記録を確認してください。');
        }

        Storage::disk('s3')->deleteDirectory("procedure-task-documents/{$office->id}");
        Storage::disk('s3')->deleteDirectory("client-reports/{$office->id}");

        Log::info('事務所を物理削除しました', [
            'office_id' => $office->id,
            'name' => $office->name,
            'office_code' => $office->office_code,
            'deleted_at' => $office->deleted_at?->toDateTimeString(),
            'purged_at' => now()->toDateTimeString(),
        ]);

        $officeName = $office->name;
        $office->forceDelete();

        return redirect()->route('platform.offices.index', ['trashed' => true])
            ->with('success', "事務所「{$officeName}」のデータを物理削除しました。この操作は取り消せません。");
    }
}
