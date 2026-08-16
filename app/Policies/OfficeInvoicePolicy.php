<?php

namespace App\Policies;

use App\Models\OfficeInvoice;
use App\Models\User;

/**
 * 請求情報の閲覧はowner限定（企画書7.5章の「顧問先削除・ユーザー管理」と同様、
 * 事務所全体に影響する機微な情報のためstaffへの個別委譲対象には含めない）。
 * Global Scope（BelongsToOffice）に加え、office_id一致もここで明示チェックする
 * （UserPolicy・ClientPolicyと同じ二重防御）。
 */
class OfficeInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, OfficeInvoice $officeInvoice): bool
    {
        return $user->isOwner() && $user->office_id === $officeInvoice->office_id;
    }
}
