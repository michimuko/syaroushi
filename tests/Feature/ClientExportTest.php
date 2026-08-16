<?php

use App\Models\Client;
use App\Models\CustomFieldDefinition;
use App\Models\Office;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

function readExportedSheet(StreamedResponse $response, string $extension): Worksheet
{
    $path = tempnam(sys_get_temp_dir(), 'export').'.'.$extension;
    ob_start();
    $response->sendContent();
    file_put_contents($path, ob_get_clean());

    $sheet = IOFactory::load($path)->getActiveSheet();
    @unlink($path);

    return $sheet;
}

it('exports only the current office\'s clients as xlsx', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    Client::factory()->for($office)->create(['name' => '自事務所の顧問先']);
    Client::factory()->for($otherOffice)->create(['name' => '他事務所の顧問先']);

    $response = $this->actingAs($user)->get(route('clients.export', ['format' => 'xlsx']));

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename="顧問先一覧.xlsx"');

    $sheet = readExportedSheet($response->baseResponse, 'xlsx');

    expect($sheet->getCell('A1')->getValue())->toBe('顧問先名')
        ->and($sheet->getCell('A2')->getValue())->toBe('自事務所の顧問先')
        ->and($sheet->getCell('A3')->getValue())->toBeNull();
});

it('exports the status as a Japanese label', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    Client::factory()->for($office)->inactive()->create(['name' => '契約終了顧問先']);

    $response = $this->actingAs($user)->get(route('clients.export', ['format' => 'xlsx']));

    $sheet = readExportedSheet($response->baseResponse, 'xlsx');

    expect($sheet->getCell('G2')->getValue())->toBe('契約終了');
});

it('exports as csv when requested', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    Client::factory()->for($office)->create(['name' => 'CSV対象顧問先']);

    $response = $this->actingAs($user)->get(route('clients.export', ['format' => 'csv']));

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename="顧問先一覧.csv"');
});

it('requires authentication', function () {
    $response = $this->get(route('clients.export'));

    $response->assertRedirect(route('login'));
});

it('appends custom field columns and formats a checkbox value as TRUE/FALSE', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $text = CustomFieldDefinition::factory()->for($office)->create(['label' => '管理番号', 'field_type' => 'text']);
    $checkbox = CustomFieldDefinition::factory()->for($office)->create(['label' => '要注意', 'field_type' => 'checkbox']);
    Client::factory()->for($office)->create([
        'name' => 'カスタム項目対象',
        'custom_fields' => [$text->id => 'A-001', $checkbox->id => true],
    ]);

    $response = $this->actingAs($user)->get(route('clients.export', ['format' => 'xlsx']));

    $sheet = readExportedSheet($response->baseResponse, 'xlsx');

    expect($sheet->getCell('J1')->getValue())->toBe('管理番号')
        ->and($sheet->getCell('K1')->getValue())->toBe('要注意')
        ->and($sheet->getCell('J2')->getValue())->toBe('A-001')
        ->and($sheet->getCell('K2')->getValue())->toBe('TRUE');
});

it('does not add custom field columns when the office has none defined', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    Client::factory()->for($office)->create(['name' => '通常の顧問先']);

    $response = $this->actingAs($user)->get(route('clients.export', ['format' => 'xlsx']));

    $sheet = readExportedSheet($response->baseResponse, 'xlsx');

    expect($sheet->getCell('J1')->getValue())->toBeNull();
});
