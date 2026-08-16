<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case Slack = 'slack';
    case Line = 'line';
    case WebPush = 'webpush';
    case Desktop = 'desktop';
}
