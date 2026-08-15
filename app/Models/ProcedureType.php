<?php

namespace App\Models;

use App\Enums\RecurrenceType;
use Database\Factories\ProcedureTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 全事務所共有のグローバルマスタ（企画書7.6章）。office_idを持たない。
 */
#[Fillable([
    'name',
    'category',
    'recurrence_type',
    'recurrence_rule',
    'default_lead_days',
    'description',
    'is_active',
])]
class ProcedureType extends Model
{
    /** @use HasFactory<ProcedureTypeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'recurrence_type' => RecurrenceType::class,
            'recurrence_rule' => 'array',
            'default_lead_days' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
