<?php

namespace App\Models;

use App\Enums\CustomFieldTarget;
use App\Enums\CustomFieldType;
use App\Models\Concerns\BelongsToOffice;
use Database\Factories\CustomFieldDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 顧問先／手続きタスクのカスタムフィールド定義（企画書5-E）。
 * 値そのものはclients.custom_fields／procedure_tasks.custom_fieldsに、
 * この定義のidをキーとしたJSONとして保存する（ユーザーにキー文字列を
 * 入力させないための設計。ラベルはいつでも自由にリネームできる）。
 */
#[Fillable(['office_id', 'target', 'label', 'field_type', 'options'])]
class CustomFieldDefinition extends Model
{
    /** @use HasFactory<CustomFieldDefinitionFactory> */
    use BelongsToOffice, HasFactory;

    protected function casts(): array
    {
        return [
            'target' => CustomFieldTarget::class,
            'field_type' => CustomFieldType::class,
            'options' => 'array',
        ];
    }
}
