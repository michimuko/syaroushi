<?php

namespace App\Models;

use App\Enums\ClientStatus;
use Database\Factories\OfficeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'contract_plan', 'is_active', 'trial_ends_at'])]
class Office extends Model
{
    /** @use HasFactory<OfficeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'date',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(OfficeInvoice::class);
    }

    /**
     * トライアル終了日が未来（または未設定でない）場合はトライアル中とみなす。
     */
    public function isTrialActive(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    /**
     * 課金対象となる顧問先数（契約終了済みの顧問先はカウントしない）。
     */
    public function billableClientCount(): int
    {
        return $this->clients()->where('status', ClientStatus::Active)->count();
    }
}
