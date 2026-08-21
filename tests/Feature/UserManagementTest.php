<?php

use App\Enums\Permission;
use App\Models\Office;
use App\Models\User;

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

test('login_id must be unique across offices when creating a user', function () {
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
