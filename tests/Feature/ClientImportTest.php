<?php

use App\Models\Client;
use App\Models\CustomFieldDefinition;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function clientImportInertiaProps(TestResponse $response): array
{
    return json_decode(json_encode($response->viewData('page')), true)['props'];
}

/**
 * @param  array<int,string>  $headers
 * @param  array<int,array<int,mixed>>  $rows
 */
function buildClientImportXlsx(array $headers, array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($headers, null, 'A1');

    $rowIndex = 2;
    foreach ($rows as $row) {
        $sheet->fromArray($row, null, 'A'.$rowIndex);
        $rowIndex++;
    }

    $path = tempnam(sys_get_temp_dir(), 'clients-import').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'clients.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

beforeEach(function () {
    Storage::fake('local');
});

it('requires the owner role to view the upload screen', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();

    $this->actingAs($staff)->get(route('clients.import.create'))->assertForbidden();
});

it('allows an owner to view the upload screen', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();

    $this->actingAs($owner)->get(route('clients.import.create'))->assertOk();
});

it('parses the uploaded file and shows a mapping screen with preview rows', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $file = buildClientImportXlsx(
        ['name', 'phone'],
        [['アルファ商事', '03-1111-2222']],
    );

    $response = $this->actingAs($owner)->post(route('clients.import.preview'), ['file' => $file]);

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('Imports/Mapping')
        ->where('headers', ['name', 'phone'])
    );

    $props = clientImportInertiaProps($response);
    expect($props['token'])->toBeString()
        ->and($props['previewRows'][0])->toBe(['アルファ商事', '03-1111-2222']);
});

it('runs the full wizard end to end and creates clients for valid rows only', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $staff = User::factory()->for($office)->create(['name' => '担当花子']);

    $file = buildClientImportXlsx(
        ['顧問先名', '担当者名', 'ステータス', 'メモ'],
        [
            ['アルファ商事', '担当花子', '契約中', ''],
            ['', '担当花子', '契約中', ''], // 顧問先名が空 → エラー
        ],
    );

    $preview = $this->actingAs($owner)->post(route('clients.import.preview'), ['file' => $file]);
    $token = clientImportInertiaProps($preview)['token'];

    $mapping = [0 => 'name', 1 => 'assigned_user_name', 2 => 'status', 3 => 'notes'];

    $review = $this->actingAs($owner)->post(route('clients.import.validate'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    $review->assertOk()->assertInertia(fn ($page) => $page
        ->component('Imports/Review')
        ->where('summary.total', 2)
        ->where('summary.valid', 1)
        ->where('summary.invalid', 1)
    );

    $commit = $this->actingAs($owner)->post(route('clients.import.commit'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    $commit->assertRedirect(route('clients.index'));

    expect(Client::count())->toBe(1);
    $client = Client::sole();
    expect($client->name)->toBe('アルファ商事')
        ->and($client->assigned_user_id)->toBe($staff->id)
        ->and($client->office_id)->toBe($office->id);
});

it('rejects the wizard actions for a staff member', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $file = buildClientImportXlsx(['name'], [['アルファ商事']]);

    $this->actingAs($staff)->post(route('clients.import.preview'), ['file' => $file])->assertForbidden();
    $this->actingAs($staff)->post(route('clients.import.validate'), ['token' => (string) Str::uuid(), 'mapping' => []])->assertForbidden();
    $this->actingAs($staff)->post(route('clients.import.commit'), ['token' => (string) Str::uuid(), 'mapping' => []])->assertForbidden();

    expect(Client::count())->toBe(0);
});

it('reports an error for an assigned_user_name that does not exist in the office', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $file = buildClientImportXlsx(
        ['顧問先名', '担当者名'],
        [['アルファ商事', '存在しないスタッフ']],
    );

    $preview = $this->actingAs($owner)->post(route('clients.import.preview'), ['file' => $file]);
    $token = clientImportInertiaProps($preview)['token'];

    $review = $this->actingAs($owner)->post(route('clients.import.validate'), [
        'token' => $token,
        'mapping' => [0 => 'name', 1 => 'assigned_user_name'],
    ]);

    $review->assertInertia(fn ($page) => $page->where('summary.invalid', 1));
});

it('does not resolve an assigned_user_name from another office (tenant isolation)', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    User::factory()->for($otherOffice)->create(['name' => '他事務所スタッフ']);

    $file = buildClientImportXlsx(
        ['顧問先名', '担当者名'],
        [['アルファ商事', '他事務所スタッフ']],
    );

    $preview = $this->actingAs($owner)->post(route('clients.import.preview'), ['file' => $file]);
    $token = clientImportInertiaProps($preview)['token'];

    $review = $this->actingAs($owner)->post(route('clients.import.validate'), [
        'token' => $token,
        'mapping' => [0 => 'name', 1 => 'assigned_user_name'],
    ]);

    $review->assertInertia(fn ($page) => $page->where('summary.invalid', 1));
});

it('cannot resolve a temp file token uploaded by a different office', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $otherOwner = User::factory()->for($otherOffice)->owner()->create();

    $file = buildClientImportXlsx(['name'], [['アルファ商事']]);
    $preview = $this->actingAs($owner)->post(route('clients.import.preview'), ['file' => $file]);
    $token = clientImportInertiaProps($preview)['token'];

    $this->actingAs($otherOwner)->post(route('clients.import.validate'), [
        'token' => $token,
        'mapping' => [0 => 'name'],
    ])->assertNotFound();
});

it('imports a row with a warning (my number-like notes) but still creates the record', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();

    $file = buildClientImportXlsx(
        ['顧問先名', 'メモ'],
        [['アルファ商事', '個人番号は1234-5678-9012です']],
    );

    $preview = $this->actingAs($owner)->post(route('clients.import.preview'), ['file' => $file]);
    $token = clientImportInertiaProps($preview)['token'];
    $mapping = [0 => 'name', 1 => 'notes'];

    $review = $this->actingAs($owner)->post(route('clients.import.validate'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    $review->assertInertia(fn ($page) => $page
        ->where('summary.valid', 1)
        ->where('summary.warned', 1)
    );

    $this->actingAs($owner)->post(route('clients.import.commit'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    expect(Client::sole()->notes)->toContain('1234-5678-9012');
});

it('exposes the office\'s custom field definitions as mappable target fields', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    CustomFieldDefinition::factory()->for($office)->create(['label' => '管理番号']);
    $file = buildClientImportXlsx(['name'], [['アルファ商事']]);

    $response = $this->actingAs($owner)->post(route('clients.import.preview'), ['file' => $file]);

    $props = clientImportInertiaProps($response);
    $labels = array_column($props['targetFields'], 'label');
    expect($labels)->toContain('管理番号（カスタムフィールド）');
});

it('imports a custom field value mapped from a spreadsheet column', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $text = CustomFieldDefinition::factory()->for($office)->create(['label' => '管理番号', 'field_type' => 'text']);
    $checkbox = CustomFieldDefinition::factory()->for($office)->create(['label' => '要注意', 'field_type' => 'checkbox']);

    $file = buildClientImportXlsx(
        ['顧問先名', '管理番号', '要注意'],
        [['アルファ商事', 'A-001', 'はい']],
    );

    $preview = $this->actingAs($owner)->post(route('clients.import.preview'), ['file' => $file]);
    $token = clientImportInertiaProps($preview)['token'];
    $mapping = [0 => 'name', 1 => "custom_field_{$text->id}", 2 => "custom_field_{$checkbox->id}"];

    $this->actingAs($owner)->post(route('clients.import.validate'), [
        'token' => $token,
        'mapping' => $mapping,
    ])->assertInertia(fn ($page) => $page->where('summary.valid', 1));

    $this->actingAs($owner)->post(route('clients.import.commit'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    $client = Client::sole();
    expect($client->custom_fields[$text->id])->toBe('A-001')
        ->and($client->custom_fields[$checkbox->id])->toBeTrue();
});

it('rejects a row whose custom field value is invalid for its type', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $select = CustomFieldDefinition::factory()->for($office)->select()->create(['label' => '業界']);

    $file = buildClientImportXlsx(
        ['顧問先名', '業界'],
        [['アルファ商事', '未定義の選択肢']],
    );

    $preview = $this->actingAs($owner)->post(route('clients.import.preview'), ['file' => $file]);
    $token = clientImportInertiaProps($preview)['token'];
    $mapping = [0 => 'name', 1 => "custom_field_{$select->id}"];

    $this->actingAs($owner)->post(route('clients.import.validate'), [
        'token' => $token,
        'mapping' => $mapping,
    ])->assertInertia(fn ($page) => $page->where('summary.invalid', 1));

    $this->actingAs($owner)->post(route('clients.import.commit'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    expect(Client::count())->toBe(0);
});

it('does not offer another office\'s custom field definitions as mapping targets (tenant isolation)', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    CustomFieldDefinition::factory()->for($otherOffice)->create(['label' => '他事務所の項目']);
    $file = buildClientImportXlsx(['name'], [['アルファ商事']]);

    $response = $this->actingAs($owner)->post(route('clients.import.preview'), ['file' => $file]);

    $props = clientImportInertiaProps($response);
    $labels = array_column($props['targetFields'], 'label');
    expect($labels)->not->toContain('他事務所の項目（カスタムフィールド）');
});

it('deletes the temp file after a successful commit', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $file = buildClientImportXlsx(['name'], [['アルファ商事']]);

    $preview = $this->actingAs($owner)->post(route('clients.import.preview'), ['file' => $file]);
    $token = clientImportInertiaProps($preview)['token'];
    $mapping = [0 => 'name'];

    $this->actingAs($owner)->post(route('clients.import.commit'), [
        'token' => $token,
        'mapping' => $mapping,
    ]);

    Storage::disk('local')->assertMissing("imports/{$office->id}/{$token}");
});
