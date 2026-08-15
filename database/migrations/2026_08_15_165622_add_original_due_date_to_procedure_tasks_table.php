<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('procedure_tasks', function (Blueprint $table) {
            $table->date('original_due_date')->nullable()->after('due_date');
        });

        // 既存データは自動生成時点の期限が不明なため、現在のdue_dateを暫定値として使う
        DB::table('procedure_tasks')->update(['original_due_date' => DB::raw('due_date')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procedure_tasks', function (Blueprint $table) {
            $table->dropColumn('original_due_date');
        });
    }
};
