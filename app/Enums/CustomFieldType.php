<?php

namespace App\Enums;

enum CustomFieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Date = 'date';
    case Select = 'select';
    case Checkbox = 'checkbox';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'テキスト',
            self::Number => '数値',
            self::Date => '日付',
            self::Select => '選択肢（単一選択）',
            self::Checkbox => 'チェックボックス',
        };
    }
}
