<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('platform_admins', function (Blueprint $table) {
            $table->string('login_id')->nullable()->after('email');
        });

        $this->backfillLoginIds();

        Schema::table('platform_admins', function (Blueprint $table) {
            $table->string('login_id')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_admins', function (Blueprint $table) {
            $table->dropColumn('login_id');
        });
    }

    /**
     * 既存の運営者アカウントにメールのローカル部（@より前）を元にした一意なlogin_idを割り当てる。
     * 空になる／重複する場合は連番を付けて解決する（usersテーブルと同じ方式）。
     */
    private function backfillLoginIds(): void
    {
        $usedLoginIds = [];

        DB::table('platform_admins')->orderBy('id')->select('id', 'email')->each(function ($admin) use (&$usedLoginIds) {
            $base = Str::of($admin->email)
                ->before('@')
                ->lower()
                ->replaceMatches('/[^a-z0-9_.-]/', '');

            $base = $base->isEmpty() ? 'admin'.$admin->id : $base->value();

            $candidate = $base;
            $suffix = 1;
            while (in_array($candidate, $usedLoginIds, true)) {
                $candidate = $base.$suffix;
                $suffix++;
            }

            $usedLoginIds[] = $candidate;

            DB::table('platform_admins')->where('id', $admin->id)->update(['login_id' => $candidate]);
        });
    }
};
