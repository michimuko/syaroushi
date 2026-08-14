<?php

namespace App\Models\Concerns;

use App\Models\Office;
use App\Models\Scopes\OfficeScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * office_idを持つ全モデルに適用するマルチテナント分離トレイト（企画書7.6章）。
 * - Global Scopeで認証中ユーザーの事務所にクエリを自動的に絞り込む
 * - creating時、認証中であれば常に認証中ユーザーの事務所でoffice_idを上書きする
 *   （フォーム・APIパラメータ経由のoffice_id指定を信用しない。企画書7.6章）
 */
trait BelongsToOffice
{
    public static function bootBelongsToOffice(): void
    {
        static::addGlobalScope(new OfficeScope);

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
