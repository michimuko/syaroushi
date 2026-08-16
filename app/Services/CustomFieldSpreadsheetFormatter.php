<?php

namespace App\Services;

use App\Enums\CustomFieldType;

/**
 * カスタムフィールド値（企画書5-E）のExcel入出力時の表現変換。
 * チェックボックス型のみ、セルの文字列表現とPHPのbool値の変換が必要
 * （他の型はセルの値をそのままDB値として扱えるため変換不要）。
 */
class CustomFieldSpreadsheetFormatter
{
    /**
     * @var array<int, string>
     */
    private const TRUE_TOKENS = ['true', '1', 'はい', '有', '○'];

    /**
     * @var array<int, string>
     */
    private const FALSE_TOKENS = ['false', '0', 'いいえ', '無', '×'];

    public function formatForExport(CustomFieldType $type, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return $type === CustomFieldType::Checkbox ? ($value ? 'TRUE' : 'FALSE') : $value;
    }

    /**
     * 認識できないトークンはそのまま返す（後続のboolean検証でエラーとして扱わせるため、
     * ここでは沈黙して null 等に丸めない）。
     * 数値型は、Excelの数値セルがPhpSpreadsheetからint/floatとして渡ってくるため文字列へ揃える
     * （フォーム経由の保存値は常に文字列のため、保存形式をどちらの入力経路でも一致させる）。
     */
    public function parseForImport(CustomFieldType $type, mixed $rawValue): mixed
    {
        if ($type === CustomFieldType::Number && (is_int($rawValue) || is_float($rawValue))) {
            return (string) $rawValue;
        }

        if ($type !== CustomFieldType::Checkbox || is_bool($rawValue)) {
            return $rawValue;
        }

        $normalized = mb_strtolower(trim((string) $rawValue));

        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, self::TRUE_TOKENS, true)) {
            return true;
        }

        if (in_array($normalized, self::FALSE_TOKENS, true)) {
            return false;
        }

        return $rawValue;
    }
}
