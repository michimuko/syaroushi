<?php

namespace App\Enums;

enum CustomFieldTarget: string
{
    case Client = 'client';
    case ProcedureTask = 'procedure_task';

    public function label(): string
    {
        return match ($this) {
            self::Client => '顧問先',
            self::ProcedureTask => '手続きタスク',
        };
    }
}
