<?php

namespace App\Notifications;

use App\Models\Office;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Stripeのinvoice.payment_failed受信時に運営者(PlatformAdmin)全員へ送る通知。
 * トライアル終了や解約と違いアプリ側は自動でロックしない設計のため、
 * 運営者が能動的に気づける手段をこの通知とPlatform/Offices一覧のバッジに絞って用意する。
 */
class StripePaymentFailed extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Office $office,
        public readonly ?int $amountDue,
        public readonly ?string $hostedInvoiceUrl,
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
        $message = (new MailMessage)
            ->subject("【要確認】{$this->office->name} の決済に失敗しました")
            ->greeting("{$notifiable->name} 様")
            ->line("事務所「{$this->office->name}」（事務所番号：{$this->office->id}）のStripe決済が失敗しました。")
            ->line('カード期限切れや残高不足などが原因の可能性があります。Stripeは自動的に再請求を試みますが、解消しない場合は事務所オーナーへの連絡や個別対応をご検討ください。');

        if ($this->amountDue !== null) {
            $message->line('請求予定額：¥'.number_format($this->amountDue));
        }

        $message->action('事務所の管理画面を開く', route('platform.offices.edit', $this->office));

        if ($this->hostedInvoiceUrl !== null) {
            $message->line("Stripe側の請求書：{$this->hostedInvoiceUrl}");
        }

        return $message;
    }
}
