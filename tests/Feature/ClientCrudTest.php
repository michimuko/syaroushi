<?php

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\Office;
use App\Models\Scopes\OfficeScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('index screen can be rendered and lists only the current office\'s clients', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    Client::factory()->for($office)->create(['name' => '自事務所の顧問先']);
    Client::factory()->for($otherOffice)->create(['name' => '他事務所の顧問先']);

    $response = $this->actingAs($user)->get(route('clients.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Clients/Index')
        ->has('clients.data', 1)
        ->where('clients.data.0.name', '自事務所の顧問先')
    );
});

test('index screen filters by search keyword', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    Client::factory()->for($office)->create(['name' => 'アルファ商事']);
    Client::factory()->for($office)->create(['name' => 'ベータ工業']);

    $response = $this->actingAs($user)->get(route('clients.index', ['search' => 'アルファ']));

    $response->assertInertia(fn ($page) => $page
        ->has('clients.data', 1)
        ->where('clients.data.0.name', 'アルファ商事')
    );
});

test('index screen filters by status', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    Client::factory()->for($office)->create(['status' => ClientStatus::Active]);
    Client::factory()->for($office)->inactive()->create();

    $response = $this->actingAs($user)->get(route('clients.index', ['status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page->has('clients.data', 1));
});

test('index screen requires authentication', function () {
    $response = $this->get(route('clients.index'));

    $response->assertRedirect(route('login'));
});

test('index query does not N+1 when loading assigned users', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $staff = User::factory()->for($office)->create();

    Client::factory()->for($office)->count(5)->create(['assigned_user_id' => $staff->id]);

    DB::enableQueryLog();
    $this->actingAs($user)->get(route('clients.index'));
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queryCount)->toBeLessThan(10);
});

test('create screen can be rendered with staff options from the same office', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    User::factory()->for($otherOffice)->create();

    $response = $this->actingAs($user)->get(route('clients.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Clients/Create')
        ->has('staffOptions', 1)
    );
});

test('a client can be created with valid data', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $response = $this->actingAs($user)->post(route('clients.store'), [
        'name' => '新規株式会社',
        'representative_name' => '山田太郎',
        'phone' => '03-1234-5678',
        'email' => 'contact@example.com',
        'status' => 'active',
        'assigned_user_id' => '',
        'notes' => '',
    ]);

    $response->assertRedirect(route('clients.index'));
    $response->assertSessionHas('highlightId');

    $client = Client::sole();
    expect($client->name)->toBe('新規株式会社')
        ->and($client->office_id)->toBe($office->id)
        ->and($response->getSession()->get('highlightId'))->toBe($client->id);
});

test('creating a client redirects to the page containing it when sorted by name pushes it past page 1', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    // 一覧は name 昇順・20件/ページ。「あ」始まりの顧問先を20件作っておき、
    // 名前順で後ろになる新規顧問先が2ページ目に入ることを確認する。
    Client::factory()->for($office)->count(20)->sequence(
        fn ($sequence) => ['name' => 'あ顧問先'.str_pad($sequence->index, 2, '0', STR_PAD_LEFT)],
    )->create();

    $response = $this->actingAs($user)->post(route('clients.store'), [
        'name' => 'ん顧問先（一番後ろ）',
        'status' => 'active',
    ]);

    $newClient = Client::where('name', 'ん顧問先（一番後ろ）')->sole();
    $response->assertRedirect(route('clients.index', ['page' => 2]));
    $response->assertSessionHas('highlightId', $newClient->id);

    $page2 = $this->actingAs($user)->get(route('clients.index', ['page' => 2]));
    $page2->assertInertia(fn ($page) => $page
        ->has('clients.data', 1)
        ->where('clients.data.0.id', $newClient->id)
    );
});

test('client creation requires a name', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $response = $this->actingAs($user)->post(route('clients.store'), [
        'name' => '',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('name');
    expect(Client::count())->toBe(0);
});

test('a client cannot be created with an assigned_user_id from another office', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $foreignStaff = User::factory()->for($otherOffice)->create();

    $response = $this->actingAs($user)->post(route('clients.store'), [
        'name' => '不正割当テスト',
        'status' => 'active',
        'assigned_user_id' => $foreignStaff->id,
    ]);

    $response->assertSessionHasErrors('assigned_user_id');
});

test('create screen requires authentication', function () {
    $response = $this->get(route('clients.create'));

    $response->assertRedirect(route('login'));
});

test('edit screen can be rendered for a client in the same office', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();

    $response = $this->actingAs($user)->get(route('clients.edit', $client));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Clients/Edit')
        ->where('client.id', $client->id)
    );
});

test('a client can be updated with valid data', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create(['name' => '旧顧問先名']);

    $response = $this->actingAs($user)->put(route('clients.update', $client), [
        'name' => '新顧問先名',
        'status' => 'inactive',
    ]);

    $response->assertRedirect(route('clients.index'));

    expect($client->fresh()->name)->toBe('新顧問先名')
        ->and($client->fresh()->status)->toBe(ClientStatus::Inactive);
});

test('renaming a client redirects to the page containing it under its new sort position', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    Client::factory()->for($office)->count(20)->sequence(
        fn ($sequence) => ['name' => 'あ顧問先'.str_pad($sequence->index, 2, '0', STR_PAD_LEFT)],
    )->create();
    $client = Client::factory()->for($office)->create(['name' => 'あ改名前']);

    $response = $this->actingAs($user)->put(route('clients.update', $client), [
        'name' => 'ん改名後（一番後ろ）',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('clients.index', ['page' => 2]));
    $response->assertSessionHas('highlightId', $client->id);
});

test('a client in another office cannot be edited or updated (404)', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $foreignClient = Client::factory()->for($otherOffice)->create();

    $this->actingAs($user)->get(route('clients.edit', $foreignClient))->assertNotFound();

    $this->actingAs($user)->put(route('clients.update', $foreignClient), [
        'name' => '改ざん試行',
        'status' => 'active',
    ])->assertNotFound();

    expect($foreignClient->fresh()->name)->not->toBe('改ざん試行');
});

test('client update requires a name', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();

    $response = $this->actingAs($user)->put(route('clients.update', $client), [
        'name' => '',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('name');
});

test('an owner can delete a client in their own office', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $client = Client::factory()->for($office)->create();

    $response = $this->actingAs($owner)->delete(route('clients.destroy', $client));

    $response->assertRedirect(route('clients.index'));
    expect(Client::find($client->id))->toBeNull();
});

test('a staff member cannot delete a client (403)', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create(); // role defaults to staff
    $client = Client::factory()->for($office)->create();

    $response = $this->actingAs($staff)->delete(route('clients.destroy', $client));

    $response->assertForbidden();
    expect(Client::find($client->id))->not->toBeNull();
});

test('an owner cannot delete a client belonging to another office (404)', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $foreignClient = Client::factory()->for($otherOffice)->create();

    $response = $this->actingAs($owner)->delete(route('clients.destroy', $foreignClient));

    $response->assertNotFound();
    expect(Client::withoutGlobalScope(OfficeScope::class)->find($foreignClient->id))->not->toBeNull();
});
