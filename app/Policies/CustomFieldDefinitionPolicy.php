<?php

namespace App\Policies;

use App\Models\CustomFieldDefinition;
use App\Models\User;

/**
 * 権限モデル（企画書7.5章）：フィールド定義の追加・削除はowner限定。
 * Global Scope（BelongsToOffice）に加え、office_id一致もここで明示チェックする
 * （UserPolicy・ClientPolicyと同じ二重防御）。
 */
class CustomFieldDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, CustomFieldDefinition $customFieldDefinition): bool
    {
        return $user->isOwner() && $user->office_id === $customFieldDefinition->office_id;
    }

    public function delete(User $user, CustomFieldDefinition $customFieldDefinition): bool
    {
        return $user->isOwner() && $user->office_id === $customFieldDefinition->office_id;
    }
}
