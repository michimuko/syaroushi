<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 企画書8章：主要バッチ処理（毎日実行）
Schedule::command('procedures:generate-upcoming')->daily();
Schedule::command('procedures:send-reminders')->daily();
Schedule::command('documents:notify-retention-expiry')->daily();
Schedule::command('imports:cleanup-stale-files')->daily();
Schedule::command('offices:notify-deletion-notices')->daily();

// 企画書11章：前月分の請求記録を月初に生成
Schedule::command('billing:generate-invoices')->monthlyOn(1, '01:00');
