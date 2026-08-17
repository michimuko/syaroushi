<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 従量制（顧問先数×単価）から階層プラン制（プランの月額固定）への移行に伴う変更。
 * unit_priceは意味を失うため削除し、user_count・plan_nameをスナップショットとして追加する
 * （プラン名変更・削除後も過去請求の表示が変わらないよう文字列で保持）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_invoices', function (Blueprint $table) {
            $table->unsignedInteger('user_count')->default(0)->after('client_count')->comment('バッチ実行時点のユーザー数（スナップショット）');
            $table->string('plan_name')->nullable()->after('user_count')->comment('バッチ実行時点の契約プラン名（スナップショット）');
            $table->dropColumn('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('office_invoices', function (Blueprint $table) {
            $table->unsignedInteger('unit_price')->nullable();
            $table->dropColumn(['user_count', 'plan_name']);
        });
    }
};
