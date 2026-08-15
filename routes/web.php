<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientExportController;
use App\Http\Controllers\ClientProcedureSubscriptionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProcedureTaskController;
use App\Http\Controllers\ProcedureTaskDocumentController;
use App\Http\Controllers\ProcedureTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth:web', 'verified'])
    ->name('dashboard');

Route::middleware('auth:web')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/clients/export', [ClientExportController::class, 'index'])->name('clients.export');
    Route::resource('clients', ClientController::class);
    Route::resource('users', UserController::class)->except(['show']);
    Route::put('/clients/{client}/procedure-subscriptions', [ClientProcedureSubscriptionController::class, 'update'])
        ->name('clients.procedure-subscriptions.update');

    Route::get('/procedure-types', [ProcedureTypeController::class, 'index'])->name('procedure-types.index');
    Route::get('/procedure-types/{procedureType}/edit', [ProcedureTypeController::class, 'edit'])->name('procedure-types.edit');
    Route::put('/procedure-types/{procedureType}', [ProcedureTypeController::class, 'update'])->name('procedure-types.update');

    Route::resource('tasks', ProcedureTaskController::class)->only(['index', 'create', 'store', 'edit', 'update']);

    Route::post('/tasks/{task}/documents', [ProcedureTaskDocumentController::class, 'store'])->name('tasks.documents.store');
    Route::post('/tasks/{task}/documents/{document}/file', [ProcedureTaskDocumentController::class, 'uploadFile'])->name('tasks.documents.upload');
    Route::patch('/tasks/{task}/documents/{document}', [ProcedureTaskDocumentController::class, 'update'])->name('tasks.documents.update');
    Route::delete('/tasks/{task}/documents/{document}', [ProcedureTaskDocumentController::class, 'destroy'])->name('tasks.documents.destroy');
    Route::get('/tasks/{task}/documents/{document}/download', [ProcedureTaskDocumentController::class, 'download'])->name('tasks.documents.download');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
});

require __DIR__.'/auth.php';
