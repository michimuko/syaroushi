<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * デスクトップ通知アプリ（Tauri、企画書5-C Level2）のポーリングAPIが配信済みを記録するために
 * 'desktop'チャンネルを追加する。
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE notification_logs MODIFY channel ENUM('email', 'slack', 'line', 'webpush', 'desktop') NOT NULL");
    }

    public function down(): void
    {
        DB::table('notification_logs')->where('channel', 'desktop')->delete();
        DB::statement("ALTER TABLE notification_logs MODIFY channel ENUM('email', 'slack', 'line', 'webpush') NOT NULL");
    }
};
