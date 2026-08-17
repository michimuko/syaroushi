<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * enabled_modulesはnullable。nullは「全モジュール有効」を意味し、既存事務所の挙動を変えない
     * （App\Models\Office::hasModule()参照）。
     */
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->json('enabled_modules')->nullable()->after('contract_plan');
            $table->unsignedInteger('unit_price_override')->nullable()->after('enabled_modules');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn(['enabled_modules', 'unit_price_override']);
        });
    }
};
