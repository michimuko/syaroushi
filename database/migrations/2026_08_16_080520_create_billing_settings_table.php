<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 事務所ごとの契約プランではなく、全事務所共通のプラットフォーム全体の料金設定
     * （企画書11章：課金軸は顧問先数に応じた従量制）。単価・トライアル日数・請求サイクルは
     * Phase7着手時点では仮の初期値とし、運営者(platform)側の設定画面から変更できるようにする。
     * 常に1行のみ存在するシングルトンテーブルとして扱う。
     */
    public function up(): void
    {
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('unit_price_per_client')->comment('顧問先1件あたりの月額単価（円）');
            $table->unsignedInteger('trial_days')->comment('無料トライアル期間（日数）');
            $table->string('billing_cycle')->comment('請求サイクル（現状はmonthlyのみ）');
            $table->timestamps();
        });

        DB::table('billing_settings')->insert([
            'unit_price_per_client' => 500,
            'trial_days' => 30,
            'billing_cycle' => 'monthly',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_settings');
    }
};
