<?php

namespace App\Enums;

enum TaskStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case DocumentsCollected = 'documents_collected';
    case Submitted = 'submitted';
    case Completed = 'completed';
}
