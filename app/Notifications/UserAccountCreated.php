<?php

namespace App\Notifications;

use App\Models\Office;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ユーザー管理画面でオーナーが新規スタッフを登録した際に本人へ送る通知。
 * キュー化せず同期送信する（キューワーカーが動いていない環境でも確実に届くようにするため）。
 *
 * パスワードは本文に含めない（登録時にオーナーが設定した値をメール本文に平文で残すのは
 * セキュリティ上望ましくないため。パスワードは別途オーナーから本人へ伝える運用とする）。
 */
class UserAccountCreated extends Notification
{
    public function __construct(
        public readonly Office $office,
    ) {}

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
            ->subject("【{$this->office->name}】アカウントが作成されました")
            ->greeting("{$notifiable->name} 様")
            ->line("{$this->office->name}の業務進捗・期限管理システムのアカウントが作成されました。")
            ->line("ログイン用ユーザーID：{$notifiable->login_id}")
            ->line('パスワードは事務所の管理者にご確認ください。')
            ->action('ログイン画面を開く', route('login'))
            ->line('このメールに心当たりがない場合は、お手数ですが本メールは破棄してください。');
    }
}
