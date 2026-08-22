<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * login_idの一意性を「全事務所で唯一」から「同じ事務所内で唯一」に変更する。
 * 事業所IDが被る心配のないemailと違い、短い文字列であるlogin_idは他事務所と
 * 偶然重複して当然であり、無関係な他社に使われているせいで希望のIDを設定できない
 * というストレスを避けるため（ユーザーからのフィードバックにより設計変更）。
 * ログイン時は事務所コード（offices.office_code）で事務所を特定してから
 * login_id・パスワードを照合するため、事務所をまたいだ一意性は不要になる。
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_login_id_unique');
            $table->unique(['office_id', 'login_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['office_id', 'login_id']);
            $table->unique('login_id');
        });
    }
};
