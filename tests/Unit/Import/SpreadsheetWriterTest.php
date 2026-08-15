<?php

use App\Services\Import\SpreadsheetWriter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function () {
    $this->writer = new SpreadsheetWriter;
    $this->tempFiles = [];
});

afterEach(function () {
    foreach ($this->tempFiles as $path) {
        @unlink($path);
    }
});

function captureStreamedResponse(StreamedResponse $response, string $extension): string
{
    $path = tempnam(sys_get_temp_dir(), 'export').'.'.$extension;
    ob_start();
    $response->sendContent();
    file_put_contents($path, ob_get_clean());

    return $path;
}

it('exports headers and rows to xlsx', function () {
    $response = $this->writer->export(
        'xlsx',
        '顧問先一覧',
        ['顧問先名', '電話番号'],
        [['アルファ商事', '03-1111-2222']],
    );

    expect($response->headers->get('Content-Disposition'))->toContain('顧問先一覧.xlsx');

    $path = captureStreamedResponse($response, 'xlsx');
    $this->tempFiles[] = $path;

    $sheet = IOFactory::load($path)->getActiveSheet();
    expect($sheet->getCell('A1')->getValue())->toBe('顧問先名')
        ->and($sheet->getCell('A2')->getValue())->toBe('アルファ商事');
});

it('exports headers and rows to csv with a BOM for Excel compatibility', function () {
    $response = $this->writer->export(
        'csv',
        '顧問先一覧',
        ['顧問先名'],
        [['アルファ商事']],
    );

    $path = captureStreamedResponse($response, 'csv');
    $this->tempFiles[] = $path;

    $contents = file_get_contents($path);

    expect($contents)->toStartWith("\xEF\xBB\xBF")
        ->and($contents)->toContain('アルファ商事');
});
