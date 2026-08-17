<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * 認証中ユーザーの所属事務所（office_id）にクエリを自動的に絞り込む。
 * マルチテナント分離の一次防御（企画書7.6章）。未認証コンテキスト
 * （バッチ処理・登録前など）では絞り込みを行わない。
 *
 * 必ず`auth('web')`とガードを明示すること。裸の`auth()`ヘルパーは「その時点のデフォルト
 * ガード」を見るため、運営者(platformガード)向けの操作中にAuthenticateミドルウェアが
 * デフォルトガードをplatformへ切り替えていると、officeに属さないPlatformAdminがここに
 * 紛れ込みoffice_idがnullになりうる（App\Models\Concerns\AssignsOfficeOnCreate参照、実機検証で確認済み）。
 */
class OfficeScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth('web')->check()) {
            $builder->where($model->qualifyColumn('office_id'), auth('web')->user()->office_id);
        }
    }
}
