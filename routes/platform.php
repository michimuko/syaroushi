<?php

use App\Http\Controllers\Platform\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Platform\OfficeController;
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
    });
});
