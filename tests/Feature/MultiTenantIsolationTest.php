<?php

use App\Enums\UserRole;
use App\Models\Office;
use App\Models\Scopes\OfficeScope;
use App\Models\User;

it('scopes queries to the authenticated user\'s office only', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();

    $userA = User::factory()->for($officeA)->create();
    $userB = User::factory()->for($officeB)->create();

    $this->actingAs($userA);

    $visibleUsers = User::all();

    expect($visibleUsers)->toHaveCount(1)
        ->and($visibleUsers->first()->id)->toBe($userA->id);

    expect(User::find($userB->id))->toBeNull();
});

it('does not scope queries when unauthenticated', function () {
    Office::factory()->create();
    User::factory()->count(2)->create();

    expect(User::count())->toBe(2);
});

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

it('can bypass the office scope explicitly via withoutGlobalScope', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();

    $userA = User::factory()->for($officeA)->create();
    User::factory()->for($officeB)->create();

    $this->actingAs($userA);

    expect(User::withoutGlobalScope(OfficeScope::class)->count())->toBe(2);
});
