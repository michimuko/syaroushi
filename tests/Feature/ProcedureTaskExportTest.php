<?php

use App\Models\Client;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

function readExportedTaskSheet(StreamedResponse $response, string $extension): Worksheet
{
    $path = tempnam(sys_get_temp_dir(), 'export').'.'.$extension;
    ob_start();
    $response->sendContent();
    file_put_contents($path, ob_get_clean());

    $sheet = IOFactory::load($path)->getActiveSheet();
    @unlink($path);

    return $sheet;
}

it('exports only the current office\'s tasks as xlsx', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $client = Client::factory()->for($office)->create(['name' => '自事務所の顧問先']);
    $procedureType = ProcedureType::factory()->create(['name' => '算定基礎届']);
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'title' => '自事務所のタスク',
        'due_date' => '2026-09-01',
    ]);

    $otherClient = Client::factory()->for($otherOffice)->create();
    ProcedureTask::factory()->for($otherOffice)->create([
        'client_id' => $otherClient->id,
        'procedure_type_id' => $procedureType->id,
        'title' => '他事務所のタスク',
    ]);

    $response = $this->actingAs($user)->get(route('tasks.export', ['format' => 'xlsx']));

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename="手続きタスク一覧.xlsx"');

    $sheet = readExportedTaskSheet($response->baseResponse, 'xlsx');

    expect($sheet->getCell('A1')->getValue())->toBe('顧問先名')
        ->and($sheet->getCell('A2')->getValue())->toBe('自事務所の顧問先')
        ->and($sheet->getCell('B2')->getValue())->toBe('算定基礎届')
        ->and($sheet->getCell('C2')->getValue())->toBe('自事務所のタスク')
        ->and($sheet->getCell('A3')->getValue())->toBeNull();
});

it('exports the status as a Japanese label', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'status' => 'in_progress',
    ]);

    $response = $this->actingAs($user)->get(route('tasks.export', ['format' => 'xlsx']));

    $sheet = readExportedTaskSheet($response->baseResponse, 'xlsx');

    expect($sheet->getCell('E2')->getValue())->toBe('進行中');
});

it('exports as csv when requested', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
    ]);

    $response = $this->actingAs($user)->get(route('tasks.export', ['format' => 'csv']));

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename="手続きタスク一覧.csv"');
});

it('requires authentication', function () {
    $response = $this->get(route('tasks.export'));

    $response->assertRedirect(route('login'));
});
