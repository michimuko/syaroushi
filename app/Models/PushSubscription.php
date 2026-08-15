<?php

namespace App\Models;

use NotificationChannels\WebPush\PushSubscription as BasePushSubscription;

/**
 * office_idはマルチテナント分離の直接カラム方針（企画書7.6章）に基づき追加する拡張。
 * subscribableは本アプリではUserに限定されるため、creating時にUserのoffice_idから
 * 自動セットする（フォーム・APIパラメータからは受け取らない）。パッケージ本体の
 * HasPushSubscriptions::updatePushSubscription()がsubscribable_idを先にセットしてから
 * saveするため、この時点でsubscribable_idからoffice_idを引ける。
 */
class PushSubscription extends BasePushSubscription
{
    protected static function booted(): void
    {
        static::creating(function (self $subscription) {
            if ($subscription->office_id === null && $subscription->subscribable_type === User::class) {
                $subscription->office_id = User::query()->find($subscription->subscribable_id)?->office_id;
            }
        });
    }
}
