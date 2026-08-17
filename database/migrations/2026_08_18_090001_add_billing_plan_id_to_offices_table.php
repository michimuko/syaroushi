<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->foreignId('billing_plan_id')->nullable()->after('custom_monthly_price')
                ->constrained('billing_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('billing_plan_id');
        });
    }
};
