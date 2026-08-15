<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\ClientReport;
use App\Models\User;

/**
 * 権限モデル（企画書7.5章）：顧問先向けレポート生成はowner全件、staffは担当顧問先のみ。
 * 過去レポートの閲覧・ダウンロードも同じ範囲に揃える。
 */
class ClientReportPolicy
{
    public function viewAny(User $user, Client $client): bool
    {
        return $this->canAccess($user, $client);
    }

    public function view(User $user, ClientReport $clientReport): bool
    {
        if ($user->office_id !== $clientReport->office_id) {
            return false;
        }

        return $this->canAccess($user, $clientReport->client);
    }

    public function create(User $user, Client $client): bool
    {
        return $this->canAccess($user, $client);
    }

    private function canAccess(User $user, Client $client): bool
    {
        if ($user->office_id !== $client->office_id) {
            return false;
        }

        return $user->isOwner() || $client->assigned_user_id === $user->id;
    }
}
