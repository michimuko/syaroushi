<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            // トライアル終了後もStripe未契約のまま放置された事務所へのデータ削除ポリシー通知
            // （3段階：終了直後／警告開始日／削除予定日の数日前）を、それぞれ一度だけ送るための
            // 既読管理フラグ。運営者がtrial_ends_atを個別に延長した際はnullにリセットされる。
            $table->timestamp('trial_ended_notified_at')->nullable()->after('deleted_at');
            $table->timestamp('deletion_warning_notified_at')->nullable()->after('trial_ended_notified_at');
            $table->timestamp('deletion_final_notice_notified_at')->nullable()->after('deletion_warning_notified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn(['trial_ended_notified_at', 'deletion_warning_notified_at', 'deletion_final_notice_notified_at']);
        });
    }
};
