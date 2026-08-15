<?php

namespace App\Notifications;

use App\Models\ProcedureTaskDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class DocumentRetentionExpired extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ProcedureTaskDocument $document,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if ($notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $document = $this->document;
        $task = $document->procedureTask;

        return (new MailMessage)
            ->subject("【保存期限切れ】{$document->name}（{$task->client->name}）")
            ->greeting("{$notifiable->name} 様")
            ->line('保存期限を過ぎた書類があります。内容を確認のうえ、不要であれば手動で削除してください。')
            ->line("顧問先：{$task->client->name}")
            ->line("手続き：{$task->title}")
            ->line("書類：{$document->name}")
            ->line('保存期限：'.$document->retention_until->toDateString())
            ->action('タスクを確認する', route('tasks.edit', $task));
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        $document = $this->document;
        $task = $document->procedureTask;
        $url = route('tasks.edit', $task);

        return (new WebPushMessage)
            ->title('【保存期限切れ】'.$document->name)
            ->body("{$task->client->name}：保存期限 {$document->retention_until->toDateString()}")
            ->action('確認する', 'view')
            ->data(['url' => $url])
            ->tag("procedure-task-document-{$document->id}");
    }
}
