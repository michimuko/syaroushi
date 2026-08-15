<?php

namespace App\Services\Import;

use App\Models\Office;

/**
 * ImportProcessorとSpreadsheetReaderを組み合わせて、アップロードされたファイル全体の
 * 検証結果（行ごとのエラー・警告・確認画面用の表示値）を組み立てる。
 * マッピング確認画面の表示と、確定直前の再検証（TOCTOU対策）の両方で使う共通ロジック。
 */
class ImportReportBuilder
{
    public function __construct(private readonly SpreadsheetReader $reader) {}

    /**
     * @param  array<int, string|null>  $mapping
     * @return array{summary: array<string, int>, rows: array<int, array<string, mixed>>}
     */
    public function build(ImportProcessor $processor, string $path, array $mapping, Office $office): array
    {
        $rows = [];
        $rowNumber = 1;

        foreach ($this->reader->readAllRows($path) as $rawRow) {
            $rowNumber++;
            $mapped = $processor->mapRow($rawRow, $mapping);
            $result = $processor->validateRow($mapped, $office);

            $rows[] = [
                'row_number' => $rowNumber,
                'errors' => $result['errors'],
                'warnings' => $result['warnings'],
                'resolved' => $result['resolved'],
                'display' => $result['display'],
            ];
        }

        $validCount = count(array_filter($rows, fn ($row) => $row['errors'] === []));
        $warnedCount = count(array_filter($rows, fn ($row) => $row['warnings'] !== []));

        return [
            'summary' => [
                'total' => count($rows),
                'valid' => $validCount,
                'invalid' => count($rows) - $validCount,
                'warned' => $warnedCount,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * 列インデックス（文字列）=>フィールドkeyの生データを、列インデックス（int）=>フィールドkeyへ正規化する。
     *
     * @param  array<string, string|null>  $rawMapping
     * @return array<int, string|null>
     */
    public function normalizeMapping(array $rawMapping): array
    {
        $mapping = [];
        foreach ($rawMapping as $columnIndex => $fieldKey) {
            $mapping[(int) $columnIndex] = $fieldKey ?: null;
        }

        return $mapping;
    }
}
