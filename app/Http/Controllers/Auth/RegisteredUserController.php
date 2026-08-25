<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\BillingPlan;
use App\Models\BillingSetting;
use App\Models\Office;
use App\Models\User;
use App\Services\Stripe\StripeCheckoutStarter;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * Checkout可能な（有効かつStripe価格が設定済みの）プランのみ選択肢として提示する
     * （エンタープライズ等の個別見積りプランはセルフサインアップの対象外）。
     */
    public function create(Request $request): Response
    {
        $billingPlans = BillingPlan::query()
            ->where('is_active', true)
            ->whereNotNull('stripe_price_id')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'max_clients', 'max_users', 'monthly_price']);

        $requestedPlanId = $request->integer('plan');
        $recommended = $billingPlans->firstWhere('name', 'スタンダード');
        $selectedPlanId = $billingPlans->contains('id', $requestedPlanId)
            ? $requestedPlanId
            : ($recommended?->id ?? $billingPlans->first()?->id);

        return Inertia::render('Auth/Register', [
            'billingPlans' => $billingPlans,
            'selectedPlanId' => $selectedPlanId,
            'contactEmail' => config('app.sales_contact_email'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * 新規登録は「事務所（テナント）の新規契約」を意味するため、
     * officeを新規作成し、登録者をそのofficeのownerとして扱う。トライアル期限・プランの割当は
     * 運営者代理作成（Platform/OfficeController::store）と対称に、登録時点で必ず設定する。
     * 登録完了後はStripe Checkoutへ自動遷移させ、トライアル終了日をそのままsubscriptionの
     * trial_endとして渡す（カードは登録時に預かり、トライアル終了の瞬間にStripe側が自動課金する）。
     *
     * @throws ValidationException
     */
    public function store(Request $request, StripeCheckoutStarter $checkoutStarter): RedirectResponse|Response
    {
        $validated = $request->validate([
            'office_name' => 'required|string|max:255',
            'office_code' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:offices,office_code'],
            'name' => 'required|string|max:255',
            // login_idは事務所内でのみ一意であればよく、新規作成する事務所には
            // まだ他のユーザーがいないため、ここでの重複チェックは不要。
            'login_id' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'billing_plan_id' => [
                'required',
                Rule::exists('billing_plans', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereNotNull('stripe_price_id')),
            ],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $office = Office::create([
                'name' => $validated['office_name'],
                'office_code' => Str::lower($validated['office_code']),
                'trial_ends_at' => now()->addDays(BillingSetting::current()->trial_days),
                'billing_plan_id' => $validated['billing_plan_id'],
            ]);

            // office_idはOffice::create()直後の信頼できる値。AssignsOfficeOnCreateの
            // 「認証中のwebガードユーザーの事務所に強制」フックが割り込むと事故になるため止める
            // （このルートはguest:webでガードされ通常webガード認証中はあり得ないが、念のため）。
            return User::withoutEvents(fn () => User::create([
                'office_id' => $office->id,
                'name' => $validated['name'],
                'login_id' => $validated['login_id'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => UserRole::Owner,
            ]));
        });

        event(new Registered($user));

        Auth::login($user);

        try {
            $checkoutUrl = $checkoutStarter->start(
                office: $user->office,
                ownerEmail: $user->email,
                successUrl: route('settings.billing.index').'?checkout=success',
                cancelUrl: route('settings.billing.index').'?checkout=cancel',
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('settings.billing.index')
                ->with('error', 'お支払い方法の登録手続きの開始に失敗しました。お手数ですが、この画面から再度お手続きください。');
        }

        // Inertiaのfetch/XHR経由のリクエストでは通常のredirect()だとブラウザ遷移が起きないため、
        // 外部URL（Stripeのホスト型Checkoutページ）へはInertia::locationで完全な画面遷移をさせる。
        return Inertia::location($checkoutUrl);
    }
}
