<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 自由入力の契約プラン欄(contract_plan)を廃止し、billing_plan_id（料金プラン）に一本化する。
 * 表示・課金計算のどちらにも使われていなかった自由記述欄で、料金プランと紐づいておらず
 * 表示名が一致する保証もなかったため（実運用前に整理）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('contract_plan');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('contract_plan')->nullable();
        });
    }
};
