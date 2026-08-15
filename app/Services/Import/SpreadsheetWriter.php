<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 顧問先・手続きタスクをExcel(xlsx)/CSVへエクスポートする（Excel移行アシスタント、逆方向の書き出し）。
 */
class SpreadsheetWriter
{
    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function export(string $format, string $filenameWithoutExtension, array $headers, iterable $rows): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');

        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, 'A'.$rowIndex);
            $rowIndex++;
        }

        if ($format === 'csv') {
            $writer = new Csv($spreadsheet);
            $writer->setUseBOM(true);
            $extension = 'csv';
            $contentType = 'text/csv';
        } else {
            $writer = new Xlsx($spreadsheet);
            $extension = 'xlsx';
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }

        $filename = $filenameWithoutExtension.'.'.$extension;

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
