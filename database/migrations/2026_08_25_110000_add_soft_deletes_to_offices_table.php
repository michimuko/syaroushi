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
        Schema::table('offices', function (Blueprint $table) {
            // トライアル終了後も未払いのまま長期間放置された事務所を運営者が手動でソフト削除する
            // 際に使う（ログイン不可・一覧非表示・復元可能）。SoftDeletesにより通常のOffice::query()は
            // 自動的にソフト削除済みを除外するため、LoginRequestのoffice_code検索も追加コード無しで
            // ブロックされる。
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
