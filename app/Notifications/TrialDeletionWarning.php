<?php

namespace App\Notifications;

use App\Models\Office;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * トライアル終了から7日経過し、データ削除ポリシーの警告期間に入った事務所のownerへ送る
 * 2通目のリマインド。削除予定日（Office::scheduledDeletionAt()）を明示する。
 */
class TrialDeletionWarning extends Notification
{
    use Queueable;

    public function __construct(public readonly Office $office) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $scheduledDeletionAt = $this->office->scheduledDeletionAt();

        return (new MailMessage)
            ->subject('【重要】お支払い方法未登録のためデータ削除予定日が近づいています')
            ->greeting("{$notifiable->name} 様")
            ->line("「{$this->office->name}」は無料トライアル終了後もお支払い方法が登録されておらず、このままでは登録データが削除されます。")
            ->line('データ削除予定日：'.$scheduledDeletionAt->toDateString())
            ->line('継続してご利用いただく場合は、削除予定日までにお支払い方法をご登録ください。')
            ->action('お支払い方法を登録する', route('settings.billing.index'));
    }
}
