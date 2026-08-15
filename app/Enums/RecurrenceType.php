<?php

namespace App\Enums;

enum RecurrenceType: string
{
    case Yearly = 'yearly';
    case Monthly = 'monthly';
    case OneTime = 'one_time';
    case Custom = 'custom';
}
