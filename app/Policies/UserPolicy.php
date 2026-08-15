<?php

namespace App\Policies;

use App\Models\User;

/**
 * ユーザーマスタの権限モデル：閲覧・作成・編集・削除はowner限定。
 * updateとdeleteはさらにoffice_id一致を明示チェックする（ClientPolicyと同じ二重防御）。
 * deleteは加えて「自分自身ではない」「事務所内最後のownerではない」を守る。
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isOwner() && $user->office_id === $target->office_id;
    }

    public function delete(User $user, User $target): bool
    {
        if (! $user->isOwner() || $user->office_id !== $target->office_id) {
            return false;
        }

        if ($user->is($target)) {
            return false;
        }

        return ! $this->isLastOwner($target);
    }

    private function isLastOwner(User $target): bool
    {
        if (! $target->isOwner()) {
            return false;
        }

        return $target->office->users()->where('role', 'owner')->count() <= 1;
    }
}
