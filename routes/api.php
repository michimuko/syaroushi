<?php

use App\Http\Controllers\Api\DesktopNotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:desktop-notifications:read'])->group(function () {
    Route::get('/desktop/notifications', [DesktopNotificationController::class, 'index'])
        ->name('api.desktop.notifications.index');
});
