<?php

namespace App\Models\Concerns;

use App\Models\Office;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * office_idを持つモデル向けの基礎トレイト（企画書7.6章）。
 * - creating時、認証中であれば常に認証中ユーザーの事務所でoffice_idを上書きする
 *   （フォーム・APIパラメータ経由のoffice_id指定を信用しない）
 *
 * Userモデルはこのトレイトのみを使用し、BelongsToOffice（Global Scope付き）は使わない。
 * Global ScopeがUser::find()内でauth()->user()を呼ぶと、セッションからのユーザー復元
 * （SessionGuardのretrieveById）がそのクエリ自体を再度呼び出し、無限再帰でスタックオーバーフロー
 * を起こすため（実機検証で確認済み）。User以外のテナント配下モデルはBelongsToOfficeを使うこと。
 */
trait AssignsOfficeOnCreate
{
    public static function bootAssignsOfficeOnCreate(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->office_id = auth()->user()->office_id;
            }
        });
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
