<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * 事務所ごとの月次請求記録（企画書11章：顧問先数に応じた従量制）。
     * 決済代行連携は範囲外のため、あくまで「その月いくら請求すべきか」の記録に留める。
     * 顧問先数・単価はバッチ実行時点の値をスナップショットとして保持し、
     * 後から料金設定が変わっても過去の請求額が変わらないようにする。
     */
    public function up(): void
    {
        Schema::create('office_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('client_count');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('amount');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['office_id', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_invoices');
    }
};
