<?php

namespace App\Services\Import;

use Generator;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Stringable;

/**
 * アップロードされたExcel(xlsx/xls)・CSVファイルを解析する薄いラッパー（Excel移行アシスタント）。
 * 1行目は常にヘッダー行として扱い、以降の行のみをデータ行として返す。
 */
class SpreadsheetReader
{
    /**
     * @return array{headers: array<int,string>, rows: array<int,array<int,mixed>>}
     */
    public function readHeaderAndPreview(string $path, int $previewRows = 5): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();

        $headers = [];
        $preview = [];
        $index = 0;

        foreach ($this->iterateRows($sheet) as $row) {
            if ($index === 0) {
                $headers = $row;
                $index++;

                continue;
            }

            if (count($preview) < $previewRows && ! $this->isBlankRow($row)) {
                $preview[] = $row;
            }

            $index++;
        }

        return ['headers' => $headers, 'rows' => $preview];
    }

    /**
     * @return iterable<int, array<int,mixed>>
     */
    public function readAllRows(string $path): iterable
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $index = 0;

        foreach ($this->iterateRows($sheet) as $row) {
            if ($index === 0) {
                $index++;

                continue;
            }

            $index++;

            if ($this->isBlankRow($row)) {
                continue;
            }

            yield $row;
        }
    }

    /**
     * @return Generator<int, array<int,mixed>>
     */
    private function iterateRows(Worksheet $sheet): Generator
    {
        $highestColumn = $sheet->getHighestDataColumn();

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            $cellIterator = $row->getCellIterator('A', $highestColumn);
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $cells[] = $this->cellValue($cell);
            }

            yield $cells;
        }
    }

    private function cellValue(Cell $cell): mixed
    {
        $value = $cell->getValue();

        // Excelアプリではなくopenpyxlなどでリッチテキストとして書き出されたセルはRichTextオブジェクトになるため、
        // 文字列バリデーションが素通りできるようここで先に文字列化しておく。
        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if ($value !== null && $value !== '' && is_numeric($value) && Date::isDateTime($cell)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }

        return is_string($value) ? trim($value) : $value;
    }

    /**
     * @param  array<int,mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}
