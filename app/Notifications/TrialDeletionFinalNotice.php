<?php

namespace App\Notifications;

use App\Models\Office;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * データ削除予定日の数日前に送る最終リマインド（3通目）。
 * 実際の削除実行は運営者の手動確認を必須としているため、削除予定日を過ぎても
 * 即座に削除されるとは限らないが、案内としては予定日ベースで送る。
 */
class TrialDeletionFinalNotice extends Notification
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
            ->subject('【最終案内】まもなく登録データが削除されます')
            ->greeting("{$notifiable->name} 様")
            ->line("「{$this->office->name}」のデータ削除予定日が近づいています。")
            ->line('データ削除予定日：'.$scheduledDeletionAt->toDateString())
            ->line('継続してご利用いただく場合は、至急お支払い方法をご登録ください。ご登録がない場合、予定日以降にデータが削除される場合があります。')
            ->action('お支払い方法を登録する', route('settings.billing.index'));
    }
}
