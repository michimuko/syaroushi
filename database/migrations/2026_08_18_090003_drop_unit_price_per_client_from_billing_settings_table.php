<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 顧問先1件あたりの単価による従量制を廃止し、billing_plansによる階層プラン制へ移行するため削除。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_settings', function (Blueprint $table) {
            $table->dropColumn('unit_price_per_client');
        });
    }

    public function down(): void
    {
        Schema::table('billing_settings', function (Blueprint $table) {
            $table->unsignedInteger('unit_price_per_client')->nullable();
        });
    }
};
