<?php

use App\Models\Client;
use App\Models\Office;
use App\Models\User;

test('module-gated routes return 403 when the module is disabled for the office', function () {
    $office = Office::factory()->create(['enabled_modules' => []]);
    $owner = User::factory()->for($office)->owner()->create();
    $client = Client::factory()->for($office)->create();

    $this->actingAs($owner)->get(route('clients.import.create'))->assertForbidden();
    $this->actingAs($owner)->get(route('tasks.import.create'))->assertForbidden();
    $this->actingAs($owner)->get(route('clients.reports.index', $client))->assertForbidden();
    $this->actingAs($owner)->post(route('push-subscriptions.store'))->assertForbidden();
    $this->actingAs($owner)->get(route('calc-assistant.index'))->assertForbidden();
    $this->actingAs($owner)->get(route('settings.custom-fields.index'))->assertForbidden();
});

test('module-gated routes are reachable when the module is enabled', function () {
    $office = Office::factory()->create(['enabled_modules' => [
        'excel_migration', 'client_report_pdf', 'web_push', 'calc_assistant', 'custom_fields',
    ]]);
    $owner = User::factory()->for($office)->owner()->create();
    $client = Client::factory()->for($office)->create();

    expect($this->actingAs($owner)->get(route('clients.import.create'))->status())->not->toBe(403);
    expect($this->actingAs($owner)->get(route('tasks.import.create'))->status())->not->toBe(403);
    expect($this->actingAs($owner)->get(route('clients.reports.index', $client))->status())->not->toBe(403);
    expect($this->actingAs($owner)->post(route('push-subscriptions.store'))->status())->not->toBe(403);
    expect($this->actingAs($owner)->get(route('calc-assistant.index'))->status())->not->toBe(403);
    expect($this->actingAs($owner)->get(route('settings.custom-fields.index'))->status())->not->toBe(403);
});

test('a null enabled_modules value means every module is enabled for legacy offices', function () {
    $office = Office::factory()->create(['enabled_modules' => null]);
    $owner = User::factory()->for($office)->owner()->create();

    $this->actingAs($owner)->get(route('calc-assistant.index'))->assertOk();
});
