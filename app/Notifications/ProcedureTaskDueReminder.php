<?php

namespace App\Notifications;

use App\Models\ProcedureTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        return ['mail'];
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
}
