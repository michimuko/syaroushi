<?php

namespace App\Notifications;

use App\Models\ProcedureTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ProcedureTaskDueReminder extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ProcedureTask $procedureTask,
        public readonly int $leadDays,
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
        $task = $this->procedureTask;

        return (new MailMessage)
            ->subject("【期限まであと{$this->leadDays}日】{$task->title}（{$task->client->name}）")
            ->greeting("{$notifiable->name} 様")
            ->line("以下の手続きの期限が近づいています。期限まであと{$this->leadDays}日です。")
            ->line("顧問先：{$task->client->name}")
            ->line("手続き：{$task->title}")
            ->line('期限日：'.$task->due_date->toDateString())
            ->action('タスクを確認する', route('tasks.edit', $task));
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        $task = $this->procedureTask;
        $url = route('tasks.edit', $task);

        return (new WebPushMessage)
            ->title("【期限まであと{$this->leadDays}日】{$task->title}")
            ->body("{$task->client->name}：期限日 {$task->due_date->toDateString()}")
            ->action('確認する', 'view')
            ->data(['url' => $url])
            ->tag("procedure-task-{$task->id}");
    }
}
