<?php

use App\Http\Controllers\Platform\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Platform\BillingPlanController;
use App\Http\Controllers\Platform\BillingSettingController;
use App\Http\Controllers\Platform\OfficeController;
use App\Http\Controllers\Platform\ReceivableController;
use Illuminate\Support\Facades\Route;

/**
 * 運営者(SaaS提供者)専用のルート。社労士側のroutes/web.php・routes/auth.phpとは
 * 完全に分離し、authミドルウェアは常にplatformガードを明示する。
 */
Route::prefix('admin')->name('platform.')->group(function () {
    Route::middleware('guest:platform')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
    });

    Route::middleware('auth:platform')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::resource('offices', OfficeController::class)->except(['show', 'destroy']);
        Route::post('offices/{office}/sync-billing', [OfficeController::class, 'syncBilling'])->name('offices.sync-billing');
        Route::post('offices/{office}/soft-delete', [OfficeController::class, 'softDelete'])->name('offices.soft-delete');
        Route::post('offices/{office}/restore', [OfficeController::class, 'restore'])->name('offices.restore')->withTrashed();
        Route::post('offices/{office}/purge', [OfficeController::class, 'purge'])->name('offices.purge')->withTrashed();
        Route::resource('billing-plans', BillingPlanController::class)->only(['index', 'store', 'update']);
        Route::put('billing-settings', [BillingSettingController::class, 'update'])->name('billing-settings.update');
        Route::get('receivables', [ReceivableController::class, 'index'])->name('receivables.index');
        Route::post('receivables/{officeInvoice}/mark-paid', [ReceivableController::class, 'markPaid'])->name('receivables.mark-paid');
    });
});
