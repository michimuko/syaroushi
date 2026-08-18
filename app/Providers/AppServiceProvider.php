<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // コンテナ経由でbindすることで、テストではfakeなStripeClientに差し替えられるようにする。
        $this->app->singleton(StripeClient::class, fn () => new StripeClient(config('services.stripe.secret')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Excelインポートウィザードの実行権限（企画書7章権限表：owner限定、
        // または`manage_imports`権限を個別付与されたstaff）。
        // モデルを持たないアクションのためPolicyではなくGateで表現する。
        Gate::define('manage-imports', fn (User $user) => $user->hasPermission(Permission::ManageImports));
    }
}
