<?php

namespace App\Models\Concerns;

use App\Models\Office;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * office_idを持つモデル向けの基礎トレイト（企画書7.6章）。
 * - creating時、社労士側(webガード)で認証中であれば常にそのユーザーの事務所で
 *   office_idを上書きする（フォーム・APIパラメータ経由のoffice_id指定を信用しない）
 *
 * 必ず`auth('web')`とガードを明示すること。バレの`auth()`ヘルパーは「その時点のデフォルト
 * ガード」を見るため、運営者(platformガード)向けの操作中に`Auth::shouldUse()`等でデフォルトが
 * 切り替わっていると、officeに属さないPlatformAdminがここに紛れ込みoffice_idがnullになりうる
 * （実機検証で確認済み）。テナント分離の根幹なので暗黙のデフォルトガードに依存しない。
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
            if (auth('web')->check()) {
                $model->office_id = auth('web')->user()->office_id;
            }
        });
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
