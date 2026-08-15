<?php

namespace App\Models\Concerns;

use App\Models\Scopes\OfficeScope;

/**
 * office_idを持つ全モデルに適用するマルチテナント分離トレイト（企画書7.6章）。
 * AssignsOfficeOnCreate（office()リレーション＋creating時の自動セット）に加えて、
 * Global Scopeで認証中ユーザーの事務所にクエリを自動的に絞り込む。
 *
 * 注意：Userモデルには使わないこと。詳細はAssignsOfficeOnCreateのdocblock参照。
 */
trait BelongsToOffice
{
    use AssignsOfficeOnCreate;

    public static function bootBelongsToOffice(): void
    {
        static::addGlobalScope(new OfficeScope);
    }
}
