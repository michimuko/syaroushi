<?php

use App\Enums\UserRole;
use App\Models\Office;
use App\Models\User;

/*
 * Userモデルは意図的にGlobal Scope（BelongsToOffice）を適用しない。
 * SessionGuardによるセッションからのユーザー復元がUser::find()相当のクエリを行うため、
 * そこでGlobal Scopeがauth()->user()を呼ぶと無限再帰でスタックオーバーフローする
 * （実機検証で確認済み。App\Models\User, App\Models\Concerns\AssignsOfficeOnCreate参照）。
 * Userの他事務所データ保護は、(1) creating時のoffice_id強制上書き、(2) Policy層のoffice_id一致チェック、
 * (3) 常にoffice()リレーション経由でクエリすること、の3点で担保する。
 * Client等の他テナントモデルのGlobal Scope分離はClientTest.phpで検証している。
 */

it('always assigns office_id from the authenticated user on create, ignoring a spoofed value', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();

    $owner = User::factory()->for($officeA)->owner()->create();
    $this->actingAs($owner);

    $staff = new User([
        'office_id' => $officeB->id, // 悪意あるフォーム入力を想定
        'name' => 'なりすまし疑いスタッフ',
        'email' => 'spoof@example.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Staff,
    ]);
    $staff->save();

    expect($staff->office_id)->toBe($officeA->id);
});

it('does not auto-assign office_id when unauthenticated (e.g. self-registration)', function () {
    $office = Office::factory()->create();

    $user = new User([
        'office_id' => $office->id,
        'name' => '新規登録者',
        'email' => 'new-owner@example.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Owner,
    ]);
    $user->save();

    expect($user->office_id)->toBe($office->id);
});

it('isolates users between offices when queried via the office relation', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();

    User::factory()->for($officeA)->count(2)->create();
    User::factory()->for($officeB)->create();

    expect($officeA->users()->count())->toBe(2)
        ->and($officeB->users()->count())->toBe(1);
});

it('can log in via session and load an authenticated page on a separate request without infinite recursion', function () {
    // actingAs()はセッション経由のユーザー復元（SessionGuard::user()）を経由しないため、
    // 過去に発生したUserモデルへのGlobal Scope起因の無限再帰バグを検知できなかった。
    // この回帰テストは実際に/loginへPOSTしてセッションを張り、別リクエストで
    // SessionGuardによるユーザー復元（User::find相当）を確実に経由させる。
    $office = Office::factory()->create();
    User::factory()->for($office)->create([
        'email' => 'session-test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->post('/login', [
        'email' => 'session-test@example.com',
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $response = $this->get(route('dashboard'));

    $response->assertOk();
});
