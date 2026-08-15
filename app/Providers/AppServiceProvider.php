<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Excelインポートウィザードの実行権限（企画書7章権限表：owner限定）。
        // モデルを持たないアクションのためPolicyではなくGateで表現する。
        Gate::define('manage-imports', fn (User $user) => $user->isOwner());
    }
}
