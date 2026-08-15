<?php

namespace App\Models;

use Database\Factories\PlatformAdminFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * SaaS運営者アカウント。office_idを持たず、usersテーブル・webガードとは
 * テーブル・認証ガードとも完全に分離する（社労士側ユーザーが構造的に
 * プラットフォーム管理機能へアクセスできないようにするための設計）。
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class PlatformAdmin extends Authenticatable
{
    /** @use HasFactory<PlatformAdminFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
