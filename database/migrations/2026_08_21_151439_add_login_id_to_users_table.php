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
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_id')->nullable()->after('email');
        });

        $this->backfillLoginIds();

        Schema::table('users', function (Blueprint $table) {
            $table->string('login_id')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('login_id');
        });
    }

    /**
     * 既存ユーザーにメールのローカル部（@より前）を元にした一意なlogin_idを割り当てる。
     * 空になる／重複する場合は連番を付けて解決する。
     */
    private function backfillLoginIds(): void
    {
        $usedLoginIds = [];

        DB::table('users')->orderBy('id')->select('id', 'email')->each(function ($user) use (&$usedLoginIds) {
            $base = Str::of($user->email)
                ->before('@')
                ->lower()
                ->replaceMatches('/[^a-z0-9_.-]/', '');

            $base = $base->isEmpty() ? 'user'.$user->id : $base->value();

            $candidate = $base;
            $suffix = 1;
            while (in_array($candidate, $usedLoginIds, true)) {
                $candidate = $base.$suffix;
                $suffix++;
            }

            $usedLoginIds[] = $candidate;

            DB::table('users')->where('id', $user->id)->update(['login_id' => $candidate]);
        });
    }
};
