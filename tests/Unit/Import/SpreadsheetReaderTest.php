<?php

use App\Services\Import\SpreadsheetReader;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->reader = new SpreadsheetReader;
    $this->tempFiles = [];
});

afterEach(function () {
    foreach ($this->tempFiles as $path) {
        @unlink($path);
    }
});

function makeXlsxFixture(array $headers, array $rows, ?callable $customize = null): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($headers, null, 'A1');

    $rowIndex = 2;
    foreach ($rows as $row) {
        $sheet->fromArray($row, null, 'A'.$rowIndex);
        $rowIndex++;
    }

    if ($customize) {
        $customize($sheet);
    }

    $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return $path;
}

it('reads headers and preview rows from an xlsx file', function () {
    $path = makeXlsxFixture(
        ['顧問先名', '代表者', '電話番号'],
        [
            ['アルファ商事', '山田太郎', '03-1111-2222'],
            ['ベータ工業', '鈴木花子', '03-3333-4444'],
        ],
    );
    $this->tempFiles[] = $path;

    $result = $this->reader->readHeaderAndPreview($path, previewRows: 5);

    expect($result['headers'])->toBe(['顧問先名', '代表者', '電話番号'])
        ->and($result['rows'])->toHaveCount(2)
        ->and($result['rows'][0])->toBe(['アルファ商事', '山田太郎', '03-1111-2222']);
});

it('limits preview rows to the requested count', function () {
    $rows = array_map(fn ($i) => ["顧問先{$i}"], range(1, 10));
    $path = makeXlsxFixture(['顧問先名'], $rows);
    $this->tempFiles[] = $path;

    $result = $this->reader->readHeaderAndPreview($path, previewRows: 3);

    expect($result['rows'])->toHaveCount(3);
});

it('skips fully blank rows', function () {
    $path = makeXlsxFixture(
        ['顧問先名'],
        [['アルファ商事'], ['', null], ['ベータ工業']],
    );
    $this->tempFiles[] = $path;

    $rows = iterator_to_array($this->reader->readAllRows($path));

    expect($rows)->toHaveCount(2);
});

it('normalizes a date-formatted cell to Y-m-d', function () {
    $path = makeXlsxFixture(
        ['契約開始日'],
        [],
        function ($sheet) {
            $sheet->setCellValue('A2', Date::PHPToExcel(new DateTime('2026-04-01')));
            $sheet->getStyle('A2')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        },
    );
    $this->tempFiles[] = $path;

    $rows = iterator_to_array($this->reader->readAllRows($path));

    expect($rows[0][0])->toBe('2026-04-01');
});

it('reads all rows from a csv file as plain strings', function () {
    $path = tempnam(sys_get_temp_dir(), 'csv').'.csv';
    $this->tempFiles[] = $path;
    file_put_contents($path, "顧問先名,契約開始日\nアルファ商事,2026-04-01\n");

    $result = $this->reader->readHeaderAndPreview($path);

    expect($result['headers'])->toBe(['顧問先名', '契約開始日'])
        ->and($result['rows'][0])->toBe(['アルファ商事', '2026-04-01']);
});
