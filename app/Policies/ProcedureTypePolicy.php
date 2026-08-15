<?php

namespace App\Policies;

use App\Models\ProcedureType;
use App\Models\User;

/**
 * 権限モデル（企画書7.5章）：閲覧はowner/staff共通、周期・通知タイミングの編集はowner限定。
 * procedure_typesは全事務所共有のグローバルマスタ（企画書7.6章）のため、
 * 編集は事務所を問わず「その手続き種別」自体に対する権限のみで判定する。
 */
class ProcedureTypePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProcedureType $procedureType): bool
    {
        return true;
    }

    public function update(User $user, ProcedureType $procedureType): bool
    {
        return $user->isOwner();
    }
}
