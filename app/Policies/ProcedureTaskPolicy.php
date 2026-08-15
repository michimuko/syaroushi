<?php

namespace App\Policies;

use App\Models\ProcedureTask;
use App\Models\User;

/**
 * 権限モデル（企画書7.5章）：閲覧・作成はowner/staff共通（全件）。
 * ステータス変更・担当者変更・メモ追記はowner全件、staffは自分がassigned_userのタスクのみ。
 */
class ProcedureTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProcedureTask $procedureTask): bool
    {
        return $user->office_id === $procedureTask->office_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ProcedureTask $procedureTask): bool
    {
        if ($user->office_id !== $procedureTask->office_id) {
            return false;
        }

        return $user->isOwner() || $procedureTask->assigned_user_id === $user->id;
    }
}
