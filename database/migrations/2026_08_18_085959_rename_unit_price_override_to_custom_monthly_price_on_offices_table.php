<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 顧問先単価の従量制上書きから、階層プラン制の個別月額上書きへ意味を変更するためのリネーム
 * （プランの月額料金より優先。null=プラン料金をそのまま適用）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->renameColumn('unit_price_override', 'custom_monthly_price');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->renameColumn('custom_monthly_price', 'unit_price_override');
        });
    }
};
