<?php

use App\Models\Client;
use App\Models\CustomFieldDefinition;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function taskImportInertiaProps(TestResponse $response): array
{
    return json_decode(json_encode($response->viewData('page')), true)['props'];
}

/**
 * @param  array<int,string>  $headers
 * @param  array<int,array<int,mixed>>  $rows
 */
function buildTaskImportXlsx(array $headers, array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($headers, null, 'A1');

    $rowIndex = 2;
    foreach ($rows as $row) {
        $sheet->fromArray($row, null, 'A'.$rowIndex);
        $rowIndex++;
    }

    $path = tempnam(sys_get_temp_dir(), 'tasks-import').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'tasks.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

/**
 * 生のテキスト内容から.csv拡張子のアップロードファイルを作る（CSV/TSV/BOM有無の検証用）。
 */
function buildTaskImportRawCsv(string $content, string $filename = 'tasks.csv'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'tasks-import').'.csv';
    file_put_contents($path, $content);

    return new UploadedFile($path, $filename, mime_content_type($path), null, true);
}

beforeEach(function () {
    Storage::fake('local');
});

it('requires the owner role to view the upload screen', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();

    $this->actingAs($staff)->get(route('tasks.import.create'))->assertForbidden();
});

it('rejects the wizard actions for a staff member', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $file = buildTaskImportXlsx(['顧問先名'], [['アルファ商事']]);

    $this->actingAs($staff)->post(route('tasks.import.preview'), ['file' => $file])->assertForbidden();
    $this->actingAs($staff)->post(route('tasks.import.validate'), ['token' => (string) Str::uuid(), 'mapping' => []])->assertForbidden();
    $this->actingAs($staff)->post(route('tasks.import.commit'), ['token' => (string) Str::uuid(), 'mapping' => []])->assertForbidden();

    expect(ProcedureTask::count())->toBe(0);
});

it('accepts a tab-separated file saved with a .csv extension', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $file = buildTaskImportRawCsv("client_name\ttitle\nアルファ商事\t算定基礎届\n");

    $response = $this->actingAs($owner)->post(route('tasks.import.preview'), ['file' => $file]);

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('Imports/Mapping')
        ->where('headers', ['client_name', 'title'])
    );
});

it('accepts a UTF-8 BOM prefixed CSV file without leaving BOM artifacts in the header', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $file = buildTaskImportRawCsv("\xEF\xBB\xBFclient_name,title\nアルファ商事,算定基礎届\n");

    $response = $this->actingAs($owner)->post(route('tasks.import.preview'), ['file' => $file]);

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('Imports/Mapping')
        ->where('headers', ['client_name', 'title'])
    );
});

it('lets an owner download an Excel import template with the same column labels used for mapping', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();

    $response = $this->actingAs($owner)->get(route('tasks.import.template'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('denies a staff member without the manage_imports permission from downloading the template', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();

    $this->actingAs($staff)->get(route('tasks.import.template'))->assertForbidden();
});

it('runs the full wizard end to end and creates tasks for valid rows only, resolving names within the office', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $staff = User::factory()->for($office)->create(['name' => '担当花子']);
    $client = Client::factory()->for($office)->create(['name' => 'アルファ商事']);
    $procedureType = ProcedureType::factory()->create(['name' => '算定基礎届']);

    $file = buildTaskImportXlsx(
        ['顧問先名', '手続き種別名', 'タイトル', '期限日', '担当者名', 'ステータス'],
        [
            ['アルファ商事', '算定基礎届', '算定基礎届の提出', '2026-09-01', '担当花子', '進行中'],
            ['存在しない顧問先', '算定基礎届', 'タイトル', '2026-09-01', '', ''], // 顧問先が見つからない → エラー
        ],
    );

    $preview = $this->actingAs($owner)->post(route('tasks.import.preview'), ['file' => $file]);
    $token = taskImportInertiaProps($preview)['token'];

    $mapping = [
        0 => 'client_name',
        1 => 'procedure_type_name',
        2 => 'title',
        3 => 'due_date',
        4 => 'assigned_user_name',
        5 => 'status',
    ];

    $review = $this->actingAs($owner)->post(route('tasks.import.validate'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    $review->assertOk()->assertInertia(fn ($page) => $page
        ->component('Imports/Review')
        ->where('summary.total', 2)
        ->where('summary.valid', 1)
        ->where('summary.invalid', 1)
    );

    $commit = $this->actingAs($owner)->post(route('tasks.import.commit'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    $commit->assertRedirect(route('tasks.index'));

    expect(ProcedureTask::count())->toBe(1);
    $task = ProcedureTask::sole();
    expect($task->client_id)->toBe($client->id)
        ->and($task->procedure_type_id)->toBe($procedureType->id)
        ->and($task->title)->toBe('算定基礎届の提出')
        ->and($task->due_date->toDateString())->toBe('2026-09-01')
        ->and($task->original_due_date->toDateString())->toBe('2026-09-01')
        ->and($task->assigned_user_id)->toBe($staff->id)
        ->and($task->status->value)->toBe('in_progress')
        ->and($task->office_id)->toBe($office->id);
});

it('does not resolve a client name from another office (tenant isolation)', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    Client::factory()->for($otherOffice)->create(['name' => '他事務所の顧問先']);
    $procedureType = ProcedureType::factory()->create(['name' => '算定基礎届']);

    $file = buildTaskImportXlsx(
        ['顧問先名', '手続き種別名', 'タイトル', '期限日'],
        [['他事務所の顧問先', '算定基礎届', 'タイトル', '2026-09-01']],
    );

    $preview = $this->actingAs($owner)->post(route('tasks.import.preview'), ['file' => $file]);
    $token = taskImportInertiaProps($preview)['token'];

    $review = $this->actingAs($owner)->post(route('tasks.import.validate'), [
        'token' => $token,
        'mapping' => [0 => 'client_name', 1 => 'procedure_type_name', 2 => 'title', 3 => 'due_date'],
    ]);

    $review->assertInertia(fn ($page) => $page->where('summary.invalid', 1));
});

it('reports a row error for an unparseable due date and does not create the task', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $client = Client::factory()->for($office)->create(['name' => 'アルファ商事']);
    $procedureType = ProcedureType::factory()->create(['name' => '算定基礎届']);

    $file = buildTaskImportXlsx(
        ['顧問先名', '手続き種別名', 'タイトル', '期限日'],
        [['アルファ商事', '算定基礎届', 'タイトル', '期限不明']],
    );

    $preview = $this->actingAs($owner)->post(route('tasks.import.preview'), ['file' => $file]);
    $token = taskImportInertiaProps($preview)['token'];
    $mapping = [0 => 'client_name', 1 => 'procedure_type_name', 2 => 'title', 3 => 'due_date'];

    $review = $this->actingAs($owner)->post(route('tasks.import.validate'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    $review->assertInertia(fn ($page) => $page->where('summary.invalid', 1));

    $this->actingAs($owner)->post(route('tasks.import.commit'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    expect(ProcedureTask::count())->toBe(0);
});

it('imports a row with a my number-like notes warning but still creates the task', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    Client::factory()->for($office)->create(['name' => 'アルファ商事']);
    ProcedureType::factory()->create(['name' => '算定基礎届']);

    $file = buildTaskImportXlsx(
        ['顧問先名', '手続き種別名', 'タイトル', '期限日', 'メモ'],
        [['アルファ商事', '算定基礎届', 'タイトル', '2026-09-01', '個人番号1234-5678-9012']],
    );

    $preview = $this->actingAs($owner)->post(route('tasks.import.preview'), ['file' => $file]);
    $token = taskImportInertiaProps($preview)['token'];
    $mapping = [0 => 'client_name', 1 => 'procedure_type_name', 2 => 'title', 3 => 'due_date', 4 => 'notes'];

    $review = $this->actingAs($owner)->post(route('tasks.import.validate'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    $review->assertInertia(fn ($page) => $page
        ->where('summary.valid', 1)
        ->where('summary.warned', 1)
    );

    $this->actingAs($owner)->post(route('tasks.import.commit'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    expect(ProcedureTask::sole()->notes)->toContain('1234-5678-9012');
});

it('imports a procedure_task-target custom field value mapped from a spreadsheet column', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    Client::factory()->for($office)->create(['name' => 'アルファ商事']);
    ProcedureType::factory()->create(['name' => '算定基礎届']);
    $number = CustomFieldDefinition::factory()->for($office)->forProcedureTask()->create(['label' => '作業時間', 'field_type' => 'number']);

    $file = buildTaskImportXlsx(
        ['顧問先名', '手続き種別名', 'タイトル', '期限日', '作業時間'],
        [['アルファ商事', '算定基礎届', 'タイトル', '2026-09-01', '3.5']],
    );

    $preview = $this->actingAs($owner)->post(route('tasks.import.preview'), ['file' => $file]);
    $token = taskImportInertiaProps($preview)['token'];
    $mapping = [0 => 'client_name', 1 => 'procedure_type_name', 2 => 'title', 3 => 'due_date', 4 => "custom_field_{$number->id}"];

    $this->actingAs($owner)->post(route('tasks.import.validate'), [
        'token' => $token,
        'mapping' => $mapping,
    ])->assertInertia(fn ($page) => $page->where('summary.valid', 1));

    $this->actingAs($owner)->post(route('tasks.import.commit'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    expect(ProcedureTask::sole()->custom_fields[$number->id])->toBe('3.5');
});

it('rejects a task row whose custom field value fails type validation', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    Client::factory()->for($office)->create(['name' => 'アルファ商事']);
    ProcedureType::factory()->create(['name' => '算定基礎届']);
    $number = CustomFieldDefinition::factory()->for($office)->forProcedureTask()->create(['label' => '作業時間', 'field_type' => 'number']);

    $file = buildTaskImportXlsx(
        ['顧問先名', '手続き種別名', 'タイトル', '期限日', '作業時間'],
        [['アルファ商事', '算定基礎届', 'タイトル', '2026-09-01', '数値ではない']],
    );

    $preview = $this->actingAs($owner)->post(route('tasks.import.preview'), ['file' => $file]);
    $token = taskImportInertiaProps($preview)['token'];
    $mapping = [0 => 'client_name', 1 => 'procedure_type_name', 2 => 'title', 3 => 'due_date', 4 => "custom_field_{$number->id}"];

    $this->actingAs($owner)->post(route('tasks.import.validate'), [
        'token' => $token,
        'mapping' => $mapping,
    ])->assertInertia(fn ($page) => $page->where('summary.invalid', 1));
});
