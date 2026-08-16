<?php

namespace App\Services\Import;

use App\Enums\CustomFieldTarget;
use App\Enums\CustomFieldType;
use App\Models\CustomFieldDefinition;
use App\Models\Office;
use App\Services\CustomFieldSpreadsheetFormatter;
use App\Services\CustomFieldValueValidator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * ClientImportProcessor・ProcedureTaskImportProcessorが共通で使う、
 * カスタムフィールド定義（企画書5-E）のマッピング項目化・値解決・検証ロジック。
 * 列キーは"custom_field_{定義id}"（システム項目keyとの衝突を避けるための命名規則）。
 */
trait ResolvesCustomFieldsForImport
{
    /**
     * インポートウィザードはアップロード行ごとにvalidateRow()を呼ぶため、
     * 同一プロセッサインスタンス内（＝1リクエスト内）ではクエリを使い回してN+1を避ける。
     *
     * @var array<string, Collection<int, CustomFieldDefinition>>
     */
    private array $customFieldDefinitionsCache = [];

    /**
     * @return Collection<int, CustomFieldDefinition>
     */
    private function customFieldDefinitions(Office $office, CustomFieldTarget $target): Collection
    {
        $cacheKey = "{$office->id}:{$target->value}";

        return $this->customFieldDefinitionsCache[$cacheKey] ??= CustomFieldDefinition::query()
            ->where('office_id', $office->id)
            ->where('target', $target)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, CustomFieldDefinition>  $definitions
     * @return array<int, array{key: string, label: string, required: bool}>
     */
    private function customFieldMappingFields(Collection $definitions): array
    {
        return $definitions
            ->map(fn (CustomFieldDefinition $definition) => [
                'key' => "custom_field_{$definition->id}",
                'label' => "{$definition->label}（カスタムフィールド）",
                'required' => false,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, CustomFieldDefinition>  $definitions
     * @param  array<string, mixed>  $mapped
     * @return array{errors: array<int, string>, values: array<int, mixed>, display: array<int, array{label: string, value: string}>}
     */
    private function resolveCustomFields(Collection $definitions, array $mapped): array
    {
        $formatter = app(CustomFieldSpreadsheetFormatter::class);

        $values = [];
        foreach ($definitions as $definition) {
            $raw = $mapped["custom_field_{$definition->id}"] ?? null;
            $values[$definition->id] = $formatter->parseForImport($definition->field_type, $raw);
        }

        $validator = Validator::make(
            ['custom_fields' => $values],
            app(CustomFieldValueValidator::class)->rules($definitions),
        );

        $errors = [];
        $display = [];
        foreach ($definitions as $definition) {
            if ($validator->errors()->has("custom_fields.{$definition->id}")) {
                $errors[] = "「{$definition->label}」の値を確認してください。";
            }

            $display[] = [
                'label' => $definition->label,
                'value' => $this->customFieldDisplayValue($definition, $values[$definition->id] ?? null),
            ];
        }

        return ['errors' => $errors, 'values' => $values, 'display' => $display];
    }

    private function customFieldDisplayValue(CustomFieldDefinition $definition, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($definition->field_type === CustomFieldType::Checkbox) {
            return is_bool($value) ? ($value ? 'はい' : 'いいえ') : (string) $value;
        }

        return (string) $value;
    }
}
