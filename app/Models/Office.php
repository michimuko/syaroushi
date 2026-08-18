<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\Module;
use Database\Factories\OfficeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'is_active', 'trial_ends_at', 'enabled_modules', 'billing_plan_id', 'custom_monthly_price',
    'stripe_customer_id', 'stripe_subscription_id', 'stripe_subscription_status',
])]
class Office extends Model
{
    /** @use HasFactory<OfficeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'date',
            'enabled_modules' => 'array',
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
