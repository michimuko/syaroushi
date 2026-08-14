<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * 認証中ユーザーの所属事務所（office_id）にクエリを自動的に絞り込む。
 * マルチテナント分離の一次防御（企画書7.6章）。未認証コンテキスト
 * （バッチ処理・登録前など）では絞り込みを行わない。
 */
class OfficeScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $builder->where($model->qualifyColumn('office_id'), auth()->user()->office_id);
        }
    }
}
