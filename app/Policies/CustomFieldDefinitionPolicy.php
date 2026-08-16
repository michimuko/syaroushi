<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\CustomFieldDefinition;
use App\Models\User;

/**
 * 権限モデル（企画書7.5章）：フィールド定義の追加・削除はowner限定
 * （または`manage_custom_fields`権限を個別付与されたstaff）。
 * Global Scope（BelongsToOffice）に加え、office_id一致もここで明示チェックする
 * （UserPolicy・ClientPolicyと同じ二重防御）。
 */
class CustomFieldDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageCustomFields);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageCustomFields);
    }

    public function update(User $user, CustomFieldDefinition $customFieldDefinition): bool
    {
        return $user->hasPermission(Permission::ManageCustomFields) && $user->office_id === $customFieldDefinition->office_id;
    }

    public function delete(User $user, CustomFieldDefinition $customFieldDefinition): bool
    {
        return $user->hasPermission(Permission::ManageCustomFields) && $user->office_id === $customFieldDefinition->office_id;
    }
}
