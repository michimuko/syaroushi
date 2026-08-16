<?php

namespace App\Enums;

enum BillingCycle: string
{
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => '月次',
        };
    }
}
