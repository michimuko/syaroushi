<?php

use App\Models\BillingPlan;
use App\Models\Office;
use App\Models\PlatformAdmin;
use App\Models\User;

test('a platform admin can create an office with its first owner in one go', function () {
    $admin = PlatformAdmin::factory()->create();

    $response = $this->actingAs($admin, 'platform')->post(route('platform.offices.store'), [
        'office_name' => '新宿社会保険労務士事務所',
        'office_code' => 'shinjuku-office',
        'owner_name' => '新規オーナー',
        'owner_login_id' => 'new-owner',
        'owner_email' => 'new-owner@example.com',
        'owner_password' => 'password123',
        'owner_password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('platform.offices.index'));

    $office = Office::where('name', '新宿社会保険労務士事務所')->sole();
    expect($office->is_active)->toBeTrue();

    $owner = User::where('email', 'new-owner@example.com')->sole();
    expect($owner->office_id)->toBe($office->id)
        ->and($owner->role->value)->toBe('owner');
});

test('a platform admin can search offices by name or office_code', function () {
    $admin = PlatformAdmin::factory()->create();
    $target = Office::factory()->create([
        'name' => '新宿社会保険労務士事務所',
        'office_code' => 'shinjuku-office',
    ]);
    Office::factory()->create([
        'name' => '渋谷社会保険労務士事務所',
        'office_code' => 'shibuya-office',
    ]);

    $byName = $this->actingAs($admin, 'platform')
        ->get(route('platform.offices.index', ['search' => '新宿']));
    $byName->assertInertia(fn ($page) => $page
        ->component('Platform/Offices/Index')
        ->has('offices.data', 1)
        ->where('offices.data.0.id', $target->id)
    );

    $byCode = $this->actingAs($admin, 'platform')
        ->get(route('platform.offices.index', ['search' => 'shinjuku']));
    $byCode->assertInertia(fn ($page) => $page
        ->has('offices.data', 1)
        ->where('offices.data.0.id', $target->id)
    );
});

test('a platform admin can filter the office list to only offices needing billing attention', function () {
    $admin = PlatformAdmin::factory()->create();
    $needsAttention = Office::factory()->create(['stripe_payment_failed_at' => now()]);
    Office::factory()->create(['trial_ends_at' => now()->addDays(20)]);

    $response = $this->actingAs($admin, 'platform')
        ->get(route('platform.offices.index', ['attention_only' => true]));

    $response->assertInertia(fn ($page) => $page
        ->component('Platform/Offices/Index')
        ->has('offices.data', 1)
        ->where('offices.data.0.id', $needsAttention->id)
        ->where('attentionCount', 1)
    );
});

test('the office list reports attentionCount across all offices regardless of the current filter or page', function () {
    $admin = PlatformAdmin::factory()->create();
    Office::factory()->create(['stripe_payment_failed_at' => now()]);
    Office::factory()->create(['trial_ends_at' => now()->subDay(), 'billing_plan_id' => null, 'custom_monthly_price' => null]);
    Office::factory()->create(['trial_ends_at' => now()->addDays(20)]);

    $response = $this->actingAs($admin, 'platform')->get(route('platform.offices.index'));

    $response->assertInertia(fn ($page) => $page->where('attentionCount', 2));
});

test('a platform admin creating an office keeps the new owner in the new office even if a web guard session for another office is active in the same browser', function () {
    $admin = PlatformAdmin::factory()->create();
    $existingOffice = Office::factory()->create();
    $existingOwner = User::factory()->for($existingOffice)->owner()->create();

    $response = $this
        ->actingAs($existingOwner, 'web')
        ->actingAs($admin, 'platform')
        ->post(route('platform.offices.store'), [
            'office_name' => '渋谷社会保険労務士事務所',
            'office_code' => 'shibuya-office',
            'owner_name' => '新規オーナー2',
            'owner_login_id' => 'new-owner-2',
            'owner_email' => 'new-owner-2@example.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

    $response->assertRedirect(route('platform.offices.index'));

    $office = Office::where('name', '渋谷社会保険労務士事務所')->sole();
    $owner = User::where('email', 'new-owner-2@example.com')->sole();

    expect($owner->office_id)->toBe($office->id)
        ->and($owner->office_id)->not->toBe($existingOffice->id);
});

test('office creation rolls back entirely if the owner email is already taken', function () {
    $admin = PlatformAdmin::factory()->create();
    $existingOffice = Office::factory()->create();
    User::factory()->for($existingOffice)->create(['email' => 'taken@example.com']);

    $response = $this->actingAs($admin, 'platform')->post(route('platform.offices.store'), [
        'office_name' => '重複テスト事務所',
        'office_code' => 'duplicate-test-office',
        'owner_name' => 'テスト',
        'owner_login_id' => 'duplicate-test',
        'owner_email' => 'taken@example.com',
        'owner_password' => 'password123',
        'owner_password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('owner_email');
    expect(Office::where('name', '重複テスト事務所')->exists())->toBeFalse();
});

test('office_code must be unique when creating an office via the platform', function () {
    $admin = PlatformAdmin::factory()->create();
    Office::factory()->create(['office_code' => 'taken-office-code']);

    $response = $this->actingAs($admin, 'platform')->post(route('platform.offices.store'), [
        'office_name' => '重複コードテスト事務所',
        'office_code' => 'taken-office-code',
        'owner_name' => 'テスト',
        'owner_login_id' => 'code-dup-owner',
        'owner_email' => 'code-dup-owner@example.com',
        'owner_password' => 'password123',
        'owner_password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('office_code');
    expect(Office::where('name', '重複コードテスト事務所')->exists())->toBeFalse();
});

test('office_code must remain unique when a platform admin edits an office', function () {
    $admin = PlatformAdmin::factory()->create();
    $officeA = Office::factory()->create(['office_code' => 'office-a']);
    $officeB = Office::factory()->create(['office_code' => 'office-b']);

    $response = $this->actingAs($admin, 'platform')->put(route('platform.offices.update', $officeB), [
        'name' => $officeB->name,
        'office_code' => $officeA->office_code,
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('office_code');
    expect($officeB->fresh()->office_code)->toBe('office-b');
});

test('a platform admin can change an office\'s office_code to a new unused value', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create(['office_code' => 'old-code']);

    $response = $this->actingAs($admin, 'platform')->put(route('platform.offices.update', $office), [
        'name' => $office->name,
        'office_code' => 'New-Code',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('platform.offices.index'));
    expect($office->fresh()->office_code)->toBe('new-code');
});

test('a platform admin can toggle an office\'s is_active flag', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create();

    $response = $this->actingAs($admin, 'platform')->put(route('platform.offices.update', $office), [
        'name' => $office->name,
        'office_code' => $office->office_code,
        'is_active' => false,
    ]);

    $response->assertRedirect(route('platform.offices.index'));
    expect($office->fresh()->is_active)->toBeFalse();
});

test('a platform admin can assign a billing plan and a custom monthly price to an office', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create();
    $plan = BillingPlan::factory()->create();

    $response = $this->actingAs($admin, 'platform')->put(route('platform.offices.update', $office), [
        'name' => $office->name,
        'office_code' => $office->office_code,
        'is_active' => true,
        'billing_plan_id' => $plan->id,
        'custom_monthly_price' => 350,
    ]);

    $response->assertRedirect(route('platform.offices.index'));
    $office->refresh();
    expect($office->billing_plan_id)->toBe($plan->id)
        ->and($office->custom_monthly_price)->toBe(350);
});

test('a platform admin can clear an office back to an unassigned plan and default pricing', function () {
    $admin = PlatformAdmin::factory()->create();
    $plan = BillingPlan::factory()->create();
    $office = Office::factory()->create([
        'billing_plan_id' => $plan->id,
        'custom_monthly_price' => 350,
    ]);

    $response = $this->actingAs($admin, 'platform')->put(route('platform.offices.update', $office), [
        'name' => $office->name,
        'office_code' => $office->office_code,
        'is_active' => true,
        'billing_plan_id' => null,
        'custom_monthly_price' => null,
    ]);

    $response->assertRedirect(route('platform.offices.index'));
    $office->refresh();
    expect($office->billing_plan_id)->toBeNull()
        ->and($office->custom_monthly_price)->toBeNull();
});
