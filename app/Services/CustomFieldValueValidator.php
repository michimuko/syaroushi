<?php

namespace App\Services;

use App\Enums\CustomFieldType;
use App\Models\CustomFieldDefinition;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * カスタムフィールド定義（企画書5-E）から、値保存時のLaravelバリデーションルールを生成する。
 * ClientController・ProcedureTaskControllerの両方が同じ形で使う共通ロジック。
 */
class CustomFieldValueValidator
{
    /**
     * @param  Collection<int, CustomFieldDefinition>  $definitions
     * @return array<string, mixed>
     */
    public function rules(Collection $definitions): array
    {
        $rules = [
            'custom_fields' => 'nullable|array',
        ];

        foreach ($definitions as $definition) {
            $key = "custom_fields.{$definition->id}";

            $rules[$key] = match ($definition->field_type) {
                CustomFieldType::Text => ['nullable', 'string', 'max:1000'],
                CustomFieldType::Number => ['nullable', 'numeric'],
                CustomFieldType::Date => ['nullable', 'date'],
                CustomFieldType::Select => ['nullable', Rule::in($definition->options ?? [])],
                CustomFieldType::Checkbox => ['nullable', 'boolean'],
            };
        }

        return $rules;
    }
}
