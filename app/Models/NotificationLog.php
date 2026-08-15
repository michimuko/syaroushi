<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Models\Concerns\BelongsToOffice;
use Database\Factories\NotificationLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['office_id', 'procedure_task_id', 'recipient_user_id', 'channel', 'lead_days', 'sent_at'])]
class NotificationLog extends Model
{
    /** @use HasFactory<NotificationLogFactory> */
    use BelongsToOffice, HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'sent_at' => 'datetime',
        ];
    }

    public function procedureTask(): BelongsTo
    {
        return $this->belongsTo(ProcedureTask::class);
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
