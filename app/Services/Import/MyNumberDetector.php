<?php

namespace App\Services\Import;

/**
 * マイナンバー（個人番号）らしき文字列を検知する（企画書7.7章）。
 * resources/js/Composables/useMyNumberDetection.js と同じ正規表現をサーバー側でも適用し、
 * Excelインポートの自由入力欄（メモ等）をチェックするために使う。
 */
class MyNumberDetector
{
    private const PATTERN = '/(?<!\d)\d{4}-?\d{4}-?\d{4}(?!\d)/u';

    public static function containsMyNumberLikeString(?string $value): bool
    {
        if (! $value) {
            return false;
        }

        return (bool) preg_match(self::PATTERN, $value);
    }
}
