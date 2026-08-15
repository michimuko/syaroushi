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
        Schema::table('procedure_task_documents', function (Blueprint $table) {
            // documents:notify-retention-expiryが同じ書類を毎日重複通知しないための既読フラグ（企画書7.7章）
            $table->timestamp('retention_notified_at')->nullable()->after('retention_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procedure_task_documents', function (Blueprint $table) {
            $table->dropColumn('retention_notified_at');
        });
    }
};
