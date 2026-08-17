<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 運営者が管理する料金プランのカタログ（企画書11章の従量制から、階層プラン制への移行）。
 * 顧問先数上限・ユーザー数上限の掛け合わせで月額を決める大手（オフィスステーション等）型の設計。
 * 初期値はオフィスステーション実勢調査を踏まえ、同等capacityで一貫して安く・ユーザー枠を広く設定。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('max_clients')->nullable()->comment('契約可能な顧問先数上限（null=無制限）');
            $table->unsignedInteger('max_users')->nullable()->comment('契約可能なユーザー数上限（null=無制限）');
            $table->unsignedInteger('monthly_price')->nullable()->comment('月額固定料金（円）。null=個別見積り');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('料金表での表示順（昇順）');
            $table->boolean('is_active')->default(true)->comment('falseの場合、事務所への新規割当対象から除外');
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        DB::table('billing_plans')->insert([
            ['name' => 'スターター', 'max_clients' => 20, 'max_users' => 3, 'monthly_price' => 8000, 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ライト', 'max_clients' => 50, 'max_users' => 6, 'monthly_price' => 13800, 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'スタンダード', 'max_clients' => 100, 'max_users' => 10, 'monthly_price' => 14800, 'sort_order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'プロフェッショナル', 'max_clients' => 200, 'max_users' => 20, 'monthly_price' => 24800, 'sort_order' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'エンタープライズ', 'max_clients' => null, 'max_users' => null, 'monthly_price' => null, 'sort_order' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_plans');
    }
};
