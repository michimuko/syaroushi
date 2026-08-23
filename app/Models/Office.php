<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\Module;
use Database\Factories\OfficeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'office_code', 'is_active', 'trial_ends_at', 'enabled_modules', 'billing_plan_id', 'custom_monthly_price',
    'stripe_customer_id', 'stripe_subscription_id', 'stripe_subscription_status', 'stripe_payment_failed_at',
])]
class Office extends Model
{
    /** @use HasFactory<OfficeFactory> */
    use HasFactory;

    /**
     * billingAttentionReasons()の'trial_ending_soon'判定・scopeNeedsBillingAttention()の
     * 両方で使う残り日数のしきい値。値をここ一箇所にまとめ、判定基準のズレを防ぐ。
     */
    public const TRIAL_ENDING_SOON_WITHIN_DAYS = 7;

    protected $appends = ['billing_attention_reasons'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'date',
            'enabled_modules' => 'array',
            'stripe_payment_failed_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(OfficeInvoice::class);
    }

    public function billingPlan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class);
    }

    /**
     * トライアル終了日が未来（または未設定でない）場合はトライアル中とみなす。
     */
    public function isTrialActive(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    /**
     * 課金対象となる顧問先数（契約終了済みの顧問先はカウントしない）。
     */
    public function billableClientCount(): int
    {
        return $this->clients()->where('status', ClientStatus::Active)->count();
    }

    public function currentUserCount(): int
    {
        return $this->users()->count();
    }

    /**
     * 割り当てられたプランの上限（顧問先数・ユーザー数）を超過している項目を返す。
     * 上限は警告表示のみに使い、登録自体はブロックしない設計（企画書11章）。
     *
     * @return list<'clients'|'users'>
     */
    public function exceedsPlanLimits(): array
    {
        $plan = $this->billingPlan;

        if ($plan === null) {
            return [];
        }

        $exceeded = [];

        if ($plan->max_clients !== null && $this->billableClientCount() > $plan->max_clients) {
            $exceeded[] = 'clients';
        }

        if ($plan->max_users !== null && $this->currentUserCount() > $plan->max_users) {
            $exceeded[] = 'users';
        }

        return $exceeded;
    }

    /**
     * Stripe側でsubscriptionがactive/trialing状態であることを指す
     * （customer.subscription.*系のwebhookでstripe_subscription_statusが更新される）。
     */
    public function hasActiveStripeSubscription(): bool
    {
        return in_array($this->stripe_subscription_status, ['active', 'trialing'], true);
    }

    /**
     * Stripeで請求サイクルが管理されている（=月次請求バッチ側でDB請求記録を重複生成してはいけない）
     * ことを指す。hasActiveStripeSubscription()と違い、支払い失敗によるpast_due／unpaid等の
     * 一時的な異常状態でも「Stripeが正」であることに変わりはないためtrueのままにする
     * （解約されてcanceledになった場合のみfalseに戻り、DBバッチでの請求記録生成対象に戻る）。
     */
    public function isStripeManaged(): bool
    {
        return $this->stripe_subscription_id !== null
            && $this->stripe_subscription_status !== null
            && $this->stripe_subscription_status !== 'canceled';
    }

    /**
     * invoice.payment_failedを最後に受けてから、まだinvoice.paidで解消されていない状態か。
     * 運営管理画面で「支払いエラー」を気づけるようにするための表示用フラグ。
     */
    public function hasStripePaymentIssue(): bool
    {
        return $this->stripe_payment_failed_at !== null;
    }

    /**
     * 運営者が気づいて対応すべき請求まわりの注意点。Platform/Offices一覧・編集画面の
     * バッジ表示に使う（判定条件をモデルに集約し、画面側では並べ替えるだけにするため）。
     *
     * @return list<'payment_failed'|'no_plan'|'trial_ending_soon'>
     */
    protected function billingAttentionReasons(): Attribute
    {
        return Attribute::get(function () {
            $reasons = [];

            if ($this->hasStripePaymentIssue()) {
                $reasons[] = 'payment_failed';
            }

            if (! $this->isTrialActive() && $this->billing_plan_id === null && $this->custom_monthly_price === null) {
                $reasons[] = 'no_plan';
            }

            if ($this->isTrialActive() && $this->trial_ends_at->lte(today()->addDays(self::TRIAL_ENDING_SOON_WITHIN_DAYS))) {
                $reasons[] = 'trial_ending_soon';
            }

            return $reasons;
        });
    }

    /**
     * billingAttentionReasons()の3条件をOR結合したクエリスコープ。一覧画面の
     * 「要対応のみ表示」フィルタ用（ページをまたいだ全件に対して正しく絞り込むため、
     * PHP側でページ内だけ判定するのではなくDBクエリとして書く）。判定基準は
     * 必ずbillingAttentionReasons()と一致させること。
     */
    public function scopeNeedsBillingAttention(Builder $query): void
    {
        $today = today();
        $trialWindowEnd = $today->copy()->addDays(self::TRIAL_ENDING_SOON_WITHIN_DAYS);

        $query->where(function (Builder $query) use ($today, $trialWindowEnd) {
            $query->whereNotNull('stripe_payment_failed_at')
                ->orWhere(function (Builder $query) use ($today) {
                    $query->whereNull('billing_plan_id')
                        ->whereNull('custom_monthly_price')
                        ->where(function (Builder $query) use ($today) {
                            $query->whereNull('trial_ends_at')
                                ->orWhere('trial_ends_at', '<=', $today->toDateString());
                        });
                })
                ->orWhere(function (Builder $query) use ($today, $trialWindowEnd) {
                    $query->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '>', $today->toDateString())
                        ->where('trial_ends_at', '<=', $trialWindowEnd->toDateString());
                });
        });
    }

    /**
     * enabled_modulesがnull（未設定）の事務所は全モジュール有効として扱う
     * （運営者がEdit画面で一度も保存していない既存事務所の後方互換のため）。
     */
    public function hasModule(Module $module): bool
    {
        if ($this->enabled_modules === null) {
            return true;
        }

        return in_array($module->value, $this->enabled_modules, true);
    }

    /**
     * @return list<string>
     */
    public function enabledModuleKeys(): array
    {
        return array_values(array_map(
            fn (Module $module) => $module->value,
            array_filter(Module::cases(), fn (Module $module) => $this->hasModule($module)),
        ));
    }
}
