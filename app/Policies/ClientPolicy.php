<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/**
 * 権限モデル（企画書7.5章）：閲覧・作成・編集はowner/staff共通、削除はowner限定。
 * Global Scope（企画書7.6章）で他事務所データは通常クエリに乗らないが、
 * ここでもoffice_id一致を明示チェックし二重防御とする。
 */
class ClientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Client $client): bool
    {
        return $user->office_id === $client->office_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Client $client): bool
    {
        return $user->office_id === $client->office_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool
    {
        return $user->office_id === $client->office_id && $user->isOwner();
    }
}
