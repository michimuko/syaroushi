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
        Schema::table('office_invoices', function (Blueprint $table) {
            // 運営者が入金確認を行った日時（未収金管理用）。nullは未収金を意味する。
            // office_invoicesはbilling:generate-invoicesバッチが生成するDB請求記録
            // （一度もStripe決済連携をしていない事務所向け）なので、Stripe側の決済状況とは無関係。
            $table->timestamp('paid_at')->nullable()->after('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('office_invoices', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });
    }
};
