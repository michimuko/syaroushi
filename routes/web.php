<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\CalcAssistantController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientExportController;
use App\Http\Controllers\ClientImportController;
use App\Http\Controllers\ClientProcedureSubscriptionController;
use App\Http\Controllers\ClientReportController;
use App\Http\Controllers\CustomFieldDefinitionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProcedureTaskCalcResultController;
use App\Http\Controllers\ProcedureTaskController;
use App\Http\Controllers\ProcedureTaskDocumentController;
use App\Http\Controllers\ProcedureTaskExportController;
use App\Http\Controllers\ProcedureTaskImportController;
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
    Route::get('/clients/import', [ClientImportController::class, 'create'])->name('clients.import.create');
    Route::post('/clients/import/preview', [ClientImportController::class, 'preview'])->name('clients.import.preview');
    Route::post('/clients/import/validate', [ClientImportController::class, 'validateMapping'])->name('clients.import.validate');
    Route::post('/clients/import/commit', [ClientImportController::class, 'commit'])->name('clients.import.commit');
    Route::resource('clients', ClientController::class);
    Route::resource('users', UserController::class)->except(['show']);
    Route::put('/clients/{client}/procedure-subscriptions', [ClientProcedureSubscriptionController::class, 'update'])
        ->name('clients.procedure-subscriptions.update');

    Route::get('/clients/{client}/reports', [ClientReportController::class, 'index'])->name('clients.reports.index');
    Route::post('/clients/{client}/reports', [ClientReportController::class, 'store'])->name('clients.reports.store');
    Route::get('/clients/{client}/reports/{report}/download', [ClientReportController::class, 'download'])->name('clients.reports.download');

    Route::get('/procedure-types', [ProcedureTypeController::class, 'index'])->name('procedure-types.index');
    Route::get('/procedure-types/{procedureType}/edit', [ProcedureTypeController::class, 'edit'])->name('procedure-types.edit');
    Route::put('/procedure-types/{procedureType}', [ProcedureTypeController::class, 'update'])->name('procedure-types.update');

    Route::get('/tasks/export', [ProcedureTaskExportController::class, 'index'])->name('tasks.export');
    Route::get('/tasks/import', [ProcedureTaskImportController::class, 'create'])->name('tasks.import.create');
    Route::post('/tasks/import/preview', [ProcedureTaskImportController::class, 'preview'])->name('tasks.import.preview');
    Route::post('/tasks/import/validate', [ProcedureTaskImportController::class, 'validateMapping'])->name('tasks.import.validate');
    Route::post('/tasks/import/commit', [ProcedureTaskImportController::class, 'commit'])->name('tasks.import.commit');
    Route::resource('tasks', ProcedureTaskController::class)->only(['index', 'create', 'store', 'edit', 'update']);

    Route::post('/tasks/{task}/documents', [ProcedureTaskDocumentController::class, 'store'])->name('tasks.documents.store');
    Route::post('/tasks/{task}/documents/{document}/file', [ProcedureTaskDocumentController::class, 'uploadFile'])->name('tasks.documents.upload');
    Route::patch('/tasks/{task}/documents/{document}', [ProcedureTaskDocumentController::class, 'update'])->name('tasks.documents.update');
    Route::delete('/tasks/{task}/documents/{document}', [ProcedureTaskDocumentController::class, 'destroy'])->name('tasks.documents.destroy');
    Route::get('/tasks/{task}/documents/{document}/download', [ProcedureTaskDocumentController::class, 'download'])->name('tasks.documents.download');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

    Route::get('/calc-assistant', [CalcAssistantController::class, 'index'])->name('calc-assistant.index');
    Route::get('/calc-assistant/paid-leave', [CalcAssistantController::class, 'showPaidLeave'])->name('calc-assistant.paid-leave');
    Route::post('/calc-assistant/paid-leave', [CalcAssistantController::class, 'calculatePaidLeave'])->name('calc-assistant.paid-leave.calculate');
    Route::get('/calc-assistant/overtime-limit', [CalcAssistantController::class, 'showOvertimeLimit'])->name('calc-assistant.overtime-limit');
    Route::post('/calc-assistant/overtime-limit', [CalcAssistantController::class, 'calculateOvertimeLimit'])->name('calc-assistant.overtime-limit.calculate');
    Route::get('/calc-assistant/shift-schedule', [CalcAssistantController::class, 'showShiftSchedule'])->name('calc-assistant.shift-schedule');
    Route::post('/calc-assistant/shift-schedule', [CalcAssistantController::class, 'calculateShiftSchedule'])->name('calc-assistant.shift-schedule.calculate');
    Route::put('/tasks/{task}/calc-result', [ProcedureTaskCalcResultController::class, 'update'])->name('tasks.calc-result.update');

    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');

    Route::get('/settings/custom-fields', [CustomFieldDefinitionController::class, 'index'])->name('settings.custom-fields.index');
    Route::post('/settings/custom-fields', [CustomFieldDefinitionController::class, 'store'])->name('settings.custom-fields.store');
    Route::put('/settings/custom-fields/{customFieldDefinition}', [CustomFieldDefinitionController::class, 'update'])->name('settings.custom-fields.update');
    Route::delete('/settings/custom-fields/{customFieldDefinition}', [CustomFieldDefinitionController::class, 'destroy'])->name('settings.custom-fields.destroy');

    Route::get('/settings/billing', [BillingController::class, 'index'])->name('settings.billing.index');
});

require __DIR__.'/auth.php';
