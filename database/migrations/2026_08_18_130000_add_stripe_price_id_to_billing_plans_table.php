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
        Schema::table('billing_plans', function (Blueprint $table) {
            // サンドボックス/本番でStripeアカウントが異なるため、環境ごとに運営者が
            // 料金プラン管理画面（/admin/billing-plans）から実際のPrice IDを設定する。
            // nullはエンタープライズ（個別見積り）等、Checkout非対応のプランを意味する。
            $table->string('stripe_price_id')->nullable()->after('monthly_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_plans', function (Blueprint $table) {
            $table->dropColumn('stripe_price_id');
        });
    }
};
