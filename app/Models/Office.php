<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\Module;
use Carbon\CarbonInterface;
use Database\Factories\OfficeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'office_code', 'is_active', 'trial_ends_at', 'enabled_modules', 'billing_plan_id', 'custom_monthly_price',
    'stripe_customer_id', 'stripe_subscription_id', 'stripe_subscription_status', 'stripe_payment_failed_at',
    'trial_ended_notified_at', 'deletion_warning_notified_at', 'deletion_final_notice_notified_at',
])]
class Office extends Model
{
    /** @use HasFactory<OfficeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * billingAttentionReasons()の'trial_ending_soon'判定・scopeNeedsBillingAttention()の
     * 両方で使う残り日数のしきい値。値をここ一箇所にまとめ、判定基準のズレを防ぐ。
     */
    public const TRIAL_ENDING_SOON_WITHIN_DAYS = 7;

    /**
     * トライアル終了後もStripe未契約のまま放置された事務所のデータ削除ポリシー（企画書・
     * 2026-08-25の方針転換）。削除予定日は常にtrial_ends_at + DATA_DELETION_SCHEDULED_AFTER_TRIAL_DAYS
     * で固定し、DATA_DELETION_WARNING_AFTER_TRIAL_DAYS経過後から請求画面・メールで警告を開始する
     * （締切自体は動かさない）。削除の実行（ソフト削除・物理削除とも）は運営者の手動確認を必須とする
     * （自動バッチでは行わない、バグによる誤削除を防ぐため）。
     */
    public const DATA_DELETION_WARNING_AFTER_TRIAL_DAYS = 7;

    public const DATA_DELETION_SCHEDULED_AFTER_TRIAL_DAYS = 60;

    public const DATA_DELETION_FINAL_NOTICE_BEFORE_DAYS = 7;

    /**
     * ソフト削除（運営者の手動確認）から物理削除が可能になるまでの猶予日数。
     * 物理削除も自動バッチでは行わず、この日数を経過した事務所を運営管理画面に
     * 表示した上で運営者が改めて手動確認する（PurgeはApp\Http\Controllers\Platform\OfficeController参照）。
     */
    public const PHYSICAL_PURGE_AFTER_SOFT_DELETE_DAYS = 30;

    protected $appends = ['billing_attention_reasons', 'eligible_for_physical_purge'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'date',
            'enabled_modules' => 'array',
            'stripe_payment_failed_at' => 'datetime',
            'trial_ended_notified_at' => 'datetime',
            'deletion_warning_notified_at' => 'datetime',
            'deletion_final_notice_notified_at' => 'datetime',
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
     * 一度でもStripe決済連携が開始された（=stripe_subscription_idが割り当てられた）ことがあるかを指す。
     * hasActiveStripeSubscription()と違い、支払い失敗によるpast_due／unpaid等の一時的な異常状態は
     * もちろん、解約されてcanceledになった後もtrueのまま変わらない。月次請求バッチ側で「一度でも
     * Stripeを経由した事務所はDB請求記録を二重生成してはいけない／解約後もDB請求を再開してはいけない」
     * 判定に使う（Stripe側が請求の正であり、解約＝サービス終了を意味するため）。
     */
    public function hasEverHadStripeSubscription(): bool
    {
        return $this->stripe_subscription_id !== null;
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
     * データ削除ポリシー上の削除予定日（trial_ends_at + 60日）。トライアル未設定なら対象外。
     */
    public function scheduledDeletionAt(): ?CarbonInterface
    {
        return $this->trial_ends_at?->copy()->addDays(self::DATA_DELETION_SCHEDULED_AFTER_TRIAL_DAYS);
    }

    /**
     * トライアルが終了しており、かつ一度もStripe決済連携をしていない（＝課金が一度も
     * 始まっていない）状態か。データ削除ポリシーの対象条件の基礎となる判定。
     */
    public function isPastTrialWithoutSubscription(): bool
    {
        return $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast()
            && ! $this->hasEverHadStripeSubscription();
    }

    /**
     * トライアル終了から7日経過し、請求画面・メールでの削除警告を出すべき期間に入っているか。
     */
    public function isInDeletionWarningPeriod(): bool
    {
        return $this->isPastTrialWithoutSubscription()
            && $this->trial_ends_at->copy()->addDays(self::DATA_DELETION_WARNING_AFTER_TRIAL_DAYS)->isPast();
    }

    /**
     * 削除予定日（trial_ends_at+60日）を過ぎており、運営者による削除操作の対象になっているか。
     * ソフト削除の実行はここがtrueの事務所に限り、運営者が手動で確認して行う。
     */
    public function isEligibleForDeletion(): bool
    {
        return $this->isPastTrialWithoutSubscription()
            && $this->scheduledDeletionAt()->isPast();
    }

    /**
     * ソフト削除済みで、かつ物理削除の猶予期間（30日）を経過しているか。
     * 物理削除自体は自動実行しないが、運営管理画面でこの条件を満たす事務所を
     * 目立たせ、運営者が手動で確認・実行できるようにする。
     */
    public function isEligibleForPhysicalPurge(): bool
    {
        return $this->trashed()
            && $this->deleted_at->copy()->addDays(self::PHYSICAL_PURGE_AFTER_SOFT_DELETE_DAYS)->isPast();
    }

    /**
     * eligible_for_physical_purgeとしてVue側に渡すための$appends用アクセサ。
     */
    protected function eligibleForPhysicalPurge(): Attribute
    {
        return Attribute::get(fn () => $this->isEligibleForPhysicalPurge());
    }

    /**
     * 運営者が気づいて対応すべき請求まわりの注意点。Platform/Offices一覧・編集画面の
     * バッジ表示に使う（判定条件をモデルに集約し、画面側では並べ替えるだけにするため）。
     *
     * @return list<'payment_failed'|'no_plan'|'trial_ending_soon'|'pending_deletion'>
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

            if ($this->isEligibleForDeletion()) {
                $reasons[] = 'pending_deletion';
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
        $deletionThreshold = $today->copy()->subDays(self::DATA_DELETION_SCHEDULED_AFTER_TRIAL_DAYS);

        $query->where(function (Builder $query) use ($today, $trialWindowEnd, $deletionThreshold) {
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
                })
                ->orWhere(function (Builder $query) use ($deletionThreshold) {
                    // isEligibleForDeletion()と一致させる：trial_ends_atが削除しきい値以前
                    // （=trial_ends_at+60日を経過）、かつ一度もStripe決済連携をしていない事務所。
                    $query->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '<=', $deletionThreshold->toDateString())
                        ->whereNull('stripe_subscription_id');
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
