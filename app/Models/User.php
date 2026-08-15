<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Models\Concerns\AssignsOfficeOnCreate;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Global Scopeによる自動絞り込み（BelongsToOffice）は使わない。
 * セッションからのユーザー復元（SessionGuard）がauth()->user()を要求するクエリ自体を
 * User::find()で行うため、Global ScopeがそこでもauthUser()を呼ぶと無限再帰になる
 * （実機検証で確認済み）。他事務所ユーザーへのアクセス制御はPolicy層と、
 * 常にoffice()経由（Auth::user()->office->users()）でクエリすることで担保する。
 */
#[Fillable(['office_id', 'name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use AssignsOfficeOnCreate, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }
}
