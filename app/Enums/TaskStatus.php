<?php

namespace App\Enums;

enum TaskStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case DocumentsCollected = 'documents_collected';
    case Submitted = 'submitted';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => '未着手',
            self::InProgress => '進行中',
            self::DocumentsCollected => '書類収集済',
            self::Submitted => '提出済',
            self::Completed => '完了',
        };
    }

    public static function fromLabel(string $label): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->label() === $label) {
                return $case;
            }
        }

        return null;
    }
}
