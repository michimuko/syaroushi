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
        Schema::table('offices', function (Blueprint $table) {
            $table->string('office_code')->nullable()->after('name');
        });

        // 既存事務所には仮の値（office-{id}）を割り当てる。運営管理画面から後で自由な文字列に変更できる。
        DB::table('offices')->orderBy('id')->select('id')->each(function ($office) {
            DB::table('offices')->where('id', $office->id)->update(['office_code' => 'office-'.$office->id]);
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->string('office_code')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('office_code');
        });
    }
};
