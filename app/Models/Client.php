<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Models\Concerns\BelongsToOffice;
use Carbon\CarbonImmutable;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'office_id',
    'name',
    'representative_name',
    'address',
    'phone',
    'email',
    'contract_start_date',
    'status',
    'assigned_user_id',
    'notes',
    'custom_fields',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToOffice, HasFactory;

    protected function casts(): array
    {
        return [
            'contract_start_date' => 'date',
            'status' => ClientStatus::class,
            'custom_fields' => 'array',
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function procedureSubscriptions(): HasMany
    {
        return $this->hasMany(ClientProcedureSubscription::class);
    }

    public function procedureTasks(): HasMany
    {
        return $this->hasMany(ProcedureTask::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ClientReport::class);
    }

    /**
     * 進捗レポート（企画書5-B）向けに、期限日が指定期間内の手続きタスクを取得する。
     */
    public function tasksDueBetween(CarbonImmutable $periodStart, CarbonImmutable $periodEnd)
    {
        return $this->procedureTasks()
            ->whereBetween('due_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->orderBy('due_date')
            ->get();
    }
}
