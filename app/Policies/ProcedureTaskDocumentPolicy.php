<?php

namespace App\Policies;

use App\Models\ProcedureTask;
use App\Models\ProcedureTaskDocument;
use App\Models\User;

/**
 * 権限モデル（企画書7.5章）：収集チェック・ファイルアップロードはowner全件、
 * staffは自分がassigned_userのタスクの書類のみ（マイナンバー等を含み得るため担当者限定）。
 * 閲覧（一覧表示）は親タスクと同じく同一事務所内なら誰でも可。
 */
class ProcedureTaskDocumentPolicy
{
    public function view(User $user, ProcedureTaskDocument $document): bool
    {
        return $user->office_id === $document->office_id;
    }

    public function create(User $user, ProcedureTask $procedureTask): bool
    {
        return $this->canManage($user, $procedureTask);
    }

    public function update(User $user, ProcedureTaskDocument $document): bool
    {
        return $this->canManage($user, $document->procedureTask);
    }

    public function delete(User $user, ProcedureTaskDocument $document): bool
    {
        return $this->canManage($user, $document->procedureTask);
    }

    private function canManage(User $user, ProcedureTask $procedureTask): bool
    {
        if ($user->office_id !== $procedureTask->office_id) {
            return false;
        }

        return $user->isOwner() || $procedureTask->assigned_user_id === $user->id;
    }
}
