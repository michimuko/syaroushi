<?php

namespace App\Notifications;

use App\Models\Office;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * トライアルが終了したがStripe決済連携が一度も行われていない事務所のownerへ、
 * 終了直後に送る1通目のリマインド。この時点ではまだデータ削除の警告期間には入っていない
 * （NotifyTrialDeletionNoticesCommand参照）。
 */
class TrialEndedWithoutPayment extends Notification
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
        return (new MailMessage)
            ->subject('無料トライアルが終了しました')
            ->greeting("{$notifiable->name} 様")
            ->line("「{$this->office->name}」の無料トライアル期間が終了しました。")
            ->line('登録いただいたデータはそのまま保持されていますが、継続してご利用いただく場合はお支払い方法のご登録が必要です。')
            ->action('お支払い方法を登録する', route('settings.billing.index'))
            ->line('このままお手続きがない場合、後日改めてご案内のうえ、一定期間後にデータを削除させていただく場合があります。');
    }
}
