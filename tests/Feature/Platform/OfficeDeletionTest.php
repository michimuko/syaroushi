<?php

use App\Models\Client;
use App\Models\Office;
use App\Models\OfficeInvoice;
use App\Models\PlatformAdmin;
use App\Models\ProcedureTask;
use App\Models\ProcedureTaskDocument;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

test('softDelete is rejected server-side when the office is not yet eligible', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDays(10)]);

    $response = $this->actingAs($admin, 'platform')
        ->post(route('platform.offices.soft-delete', $office->id));

    $response->assertRedirect();
    expect(session('error'))->not->toBeNull();
    expect($office->fresh()->trashed())->toBeFalse();
});

test('softDelete succeeds for an eligible office', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDays(61)]);

    $response = $this->actingAs($admin, 'platform')
        ->post(route('platform.offices.soft-delete', $office->id));

    $response->assertRedirect(route('platform.offices.index'));
    expect($office->fresh()->trashed())->toBeTrue();
});

test('restore brings a soft-deleted office back', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDays(61)]);
    $office->delete();

    $response = $this->actingAs($admin, 'platform')
        ->post(route('platform.offices.restore', $office->id));

    $response->assertRedirect();
    expect($office->fresh()->trashed())->toBeFalse();
});

// $this->actingAs($admin, 'platform')の後にAuth::attempt()（LoginRequest経由）を同じテスト内で
// 呼ぶと、Laravelのテストヘルパーがデフォルトガードをplatformのまま残しplatform_adminsテーブル
// に対してクエリしてしまう既知の挙動（App\Models\Scopes\OfficeScopeのdocblock参照）があるため、
// ログイン可否の検証はplatformガードでのactingAsを一切行わない独立したテストで確認する。
test('login is blocked for a soft-deleted office and works again once restored', function () {
    $office = Office::factory()->create(['is_active' => true]);
    $owner = User::factory()->for($office)->owner()->create();

    $office->delete();

    $blockedResponse = $this->post('/login', [
        'office_code' => $office->office_code,
        'login_id' => $owner->login_id,
        'password' => 'password',
    ]);
    $blockedResponse->assertSessionHasErrors('login_id');
    $this->assertGuest();

    $office->restore();

    $allowedResponse = $this->post('/login', [
        'office_code' => $office->office_code,
        'login_id' => $owner->login_id,
        'password' => 'password',
    ]);
    $allowedResponse->assertRedirect(route('dashboard', absolute: false));
});

test('purge is rejected before the 30-day physical purge grace period has passed', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDays(61)]);
    $office->delete();
    $office->forceFill(['deleted_at' => now()->subDays(10)])->saveQuietly();

    $response = $this->actingAs($admin, 'platform')
        ->post(route('platform.offices.purge', $office->id));

    $response->assertRedirect();
    expect(session('error'))->not->toBeNull();
    expect(Office::withTrashed()->find($office->id))->not->toBeNull();
});

test('purge is rejected when the office still has invoice records (financial safety valve)', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDays(61)]);
    OfficeInvoice::factory()->for($office)->create();
    $office->delete();
    $office->forceFill(['deleted_at' => now()->subDays(31)])->saveQuietly();

    $response = $this->actingAs($admin, 'platform')
        ->post(route('platform.offices.purge', $office->id));

    $response->assertRedirect();
    expect(session('error'))->not->toBeNull();
    expect(Office::withTrashed()->find($office->id))->not->toBeNull();
});

test('purge deletes S3 files, cascades all tenant data, and removes the office row entirely', function () {
    Storage::fake('s3');

    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDays(61)]);
    $user = User::factory()->for($office)->owner()->create();

    $client = Client::factory()->for($office)->create();
    $task = ProcedureTask::factory()->for($client)->create(['office_id' => $office->id]);

    Storage::disk('s3')->put("procedure-task-documents/{$office->id}/file1.pdf", 'dummy');
    ProcedureTaskDocument::factory()->create([
        'office_id' => $office->id,
        'procedure_task_id' => $task->id,
        'file_path' => "procedure-task-documents/{$office->id}/file1.pdf",
    ]);

    $office->delete();
    $office->forceFill(['deleted_at' => now()->subDays(31)])->saveQuietly();

    $response = $this->actingAs($admin, 'platform')
        ->post(route('platform.offices.purge', $office->id));

    $response->assertRedirect();
    expect(session('success'))->not->toBeNull();

    Storage::disk('s3')->assertMissing("procedure-task-documents/{$office->id}/file1.pdf");
    expect(Office::withTrashed()->find($office->id))->toBeNull();
    expect(User::find($user->id))->toBeNull();
});

test('the offices index with trashed=1 lists only soft-deleted offices', function () {
    $admin = PlatformAdmin::factory()->create();
    $active = Office::factory()->create(['name' => 'アクティブ事務所']);
    $trashed = Office::factory()->create(['name' => '削除済み事務所']);
    $trashed->delete();

    $response = $this->actingAs($admin, 'platform')
        ->get(route('platform.offices.index', ['trashed' => true]));

    $response->assertInertia(fn ($page) => $page
        ->has('offices.data', 1)
        ->where('offices.data.0.id', $trashed->id));
});

test('changing trial_ends_at on the edit form resets the deletion notification flags', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->subDays(10),
        'trial_ended_notified_at' => now(),
        'deletion_warning_notified_at' => now(),
    ]);

    $this->actingAs($admin, 'platform')->put(route('platform.offices.update', $office->id), [
        'name' => $office->name,
        'office_code' => $office->office_code,
        'is_active' => true,
        'trial_ends_at' => CarbonImmutable::today()->addDays(30)->toDateString(),
    ]);

    $office->refresh();
    expect($office->trial_ended_notified_at)->toBeNull();
    expect($office->deletion_warning_notified_at)->toBeNull();
});
