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
            // invoice.payment_failed受信時にセットし、invoice.paid受信時にnullへ戻す。
            // 運営管理画面で「支払いエラー」を一目で気づけるようにするための表示専用カラム
            // （課金ロジック自体はstripe_subscription_statusを見て判断する）。
            $table->timestamp('stripe_payment_failed_at')->nullable()->after('stripe_subscription_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('stripe_payment_failed_at');
        });
    }
};
