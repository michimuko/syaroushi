<?php

use App\Enums\Permission;
use App\Models\Office;
use App\Models\User;
use App\Notifications\UserAccountCreated;
use Illuminate\Support\Facades\Notification;

test('owner can list only their own office\'s users', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    User::factory()->for($otherOffice)->create();

    $response = $this->actingAs($owner)->get(route('users.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Users/Index')
        ->has('users.data', 1)
        ->where('users.data.0.id', $owner->id)
    );
});

test('owner can search users by name or email within their own office', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create(['name' => '事務所オーナー']);
    $target = User::factory()->for($office)->create([
        'name' => '山田太郎',
        'email' => 'yamada@example.com',
    ]);
    User::factory()->for($office)->create(['name' => '鈴木花子']);

    $byName = $this->actingAs($owner)->get(route('users.index', ['search' => '山田']));
    $byName->assertInertia(fn ($page) => $page
        ->component('Users/Index')
        ->has('users.data', 1)
        ->where('users.data.0.id', $target->id)
    );

    $byEmail = $this->actingAs($owner)->get(route('users.index', ['search' => 'yamada@']));
    $byEmail->assertInertia(fn ($page) => $page
        ->has('users.data', 1)
        ->where('users.data.0.id', $target->id)
    );
});

test('staff cannot access any user management action', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $target = User::factory()->for($office)->create();

    $this->actingAs($staff)->get(route('users.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('users.create'))->assertForbidden();
    $this->actingAs($staff)->post(route('users.store'), [])->assertForbidden();
    $this->actingAs($staff)->get(route('users.edit', $target))->assertForbidden();
    $this->actingAs($staff)->put(route('users.update', $target), [])->assertForbidden();
    $this->actingAs($staff)->delete(route('users.destroy', $target))->assertForbidden();
});

test('a newly created user is always attached to the creating owner\'s office', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();

    $response = $this->actingAs($owner)->post(route('users.store'), [
        'name' => '新しい社員',
        'login_id' => 'newstaff',
        'email' => 'newstaff@example.com',
        'role' => 'staff',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'office_id' => $otherOffice->id, // 偽装を試みる
    ]);

    $response->assertRedirect(route('users.index'));

    $created = User::where('email', 'newstaff@example.com')->sole();
    expect($created->office_id)->toBe($office->id);
});

test('a newly created user is notified by mail with their login_id but not their password', function () {
    Notification::fake();

    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();

    $this->actingAs($owner)->post(route('users.store'), [
        'name' => '通知確認太郎',
        'login_id' => 'notify-taro',
        'email' => 'notify-taro@example.com',
        'role' => 'staff',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $created = User::where('email', 'notify-taro@example.com')->sole();

    Notification::assertSentTo(
        $created,
        function (UserAccountCreated $notification, array $channels) use ($created, $office) {
            $rendered = (string) $notification->toMail($created)->render();

            return $channels === ['mail']
                && $notification->office->is($office)
                && str_contains($rendered, 'notify-taro')
                && ! str_contains($rendered, 'password123');
        }
    );
});

test('login_id must be unique within the same office when creating a user', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $existing = User::factory()->for($office)->create(['login_id' => 'taken-id']);

    $response = $this->actingAs($owner)->post(route('users.store'), [
        'name' => '重複ID社員',
        'login_id' => $existing->login_id,
        'email' => 'dup-login-id@example.com',
        'role' => 'staff',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('login_id');
    expect(User::where('email', 'dup-login-id@example.com')->exists())->toBeFalse();
});

test('the same login_id can be reused in a different office when creating a user', function () {
    $otherOffice = Office::factory()->create();
    User::factory()->for($otherOffice)->create(['login_id' => 'taro']);

    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();

    $response = $this->actingAs($owner)->post(route('users.store'), [
        'name' => '同名ID社員',
        'login_id' => 'taro',
        'email' => 'same-login-id-different-office@example.com',
        'role' => 'staff',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('users.index'));
    expect(User::where('email', 'same-login-id-different-office@example.com')->exists())->toBeTrue();
});

test('login_id validation errors are returned in clear Japanese, not the default English messages', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();

    // メールアドレスのような、許可されていない文字（@）を含むIDは正規表現ルールで弾かれる
    $response = $this->actingAs($owner)->post(route('users.store'), [
        'name' => '不正ID社員',
        'login_id' => 'not-an-id@example.com',
        'email' => 'invalid-format@example.com',
        'role' => 'staff',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('login_id');
    $message = session('errors')->first('login_id');

    expect($message)
        ->toBe('ユーザーIDは半角英数字・アンダースコア（_）・ハイフン（-）・ピリオド（.）のみ使用できます。')
        ->not->toContain('The login id field');
});

test('an owner cannot delete themselves', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();

    $this->actingAs($owner)->delete(route('users.destroy', $owner))->assertForbidden();
    expect(User::find($owner->id))->not->toBeNull();
});

test('the last owner in an office cannot be deleted or demoted', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $otherOwner = User::factory()->for($office)->owner()->create();

    // otherOwnerがいる間はownerを削除・降格できる
    $this->actingAs($otherOwner)->put(route('users.update', $owner), [
        'name' => $owner->name,
        'login_id' => $owner->login_id,
        'email' => $owner->email,
        'role' => 'staff',
    ])->assertRedirect(route('users.index'));
    expect($owner->fresh()->role->value)->toBe('staff');

    // ここでotherOwnerが唯一のownerになる
    $this->actingAs($otherOwner)->delete(route('users.destroy', $owner));

    $this->actingAs($otherOwner)->put(route('users.update', $otherOwner), [
        'name' => $otherOwner->name,
        'login_id' => $otherOwner->login_id,
        'email' => $otherOwner->email,
        'role' => 'staff',
    ])->assertSessionHasErrors('role');
    expect($otherOwner->fresh()->role->value)->toBe('owner');
});

test('an owner can grant individual permissions to a staff member', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $staff = User::factory()->for($office)->create();

    $response = $this->actingAs($owner)->put(route('users.update', $staff), [
        'name' => $staff->name,
        'login_id' => $staff->login_id,
        'email' => $staff->email,
        'role' => 'staff',
        'permissions' => ['manage_custom_fields', 'manage_imports'],
    ]);

    $response->assertRedirect(route('users.index'));
    expect($staff->fresh()->permissions)->toBe(['manage_custom_fields', 'manage_imports']);
    expect($staff->fresh()->hasPermission(Permission::ManageCustomFields))->toBeTrue();
    expect($staff->fresh()->hasPermission(Permission::ManageProcedureTypes))->toBeFalse();
});

test('permissions are cleared when a user is set (or promoted) to owner', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $staff = User::factory()->for($office)->withPermissions([Permission::ManageCustomFields])->create();

    $this->actingAs($owner)->put(route('users.update', $staff), [
        'name' => $staff->name,
        'login_id' => $staff->login_id,
        'email' => $staff->email,
        'role' => 'owner',
        'permissions' => ['manage_custom_fields'],
    ])->assertRedirect(route('users.index'));

    expect($staff->fresh()->permissions)->toBe([]);
});

test('editing or deleting a user from another office is forbidden', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $foreignUser = User::factory()->for($otherOffice)->create();

    $this->actingAs($owner)->get(route('users.edit', $foreignUser))->assertForbidden();
    $this->actingAs($owner)->put(route('users.update', $foreignUser), [
        'name' => 'なりすまし',
        'email' => $foreignUser->email,
        'role' => 'staff',
    ])->assertForbidden();
    $this->actingAs($owner)->delete(route('users.destroy', $foreignUser))->assertForbidden();
});
