<?php

use App\Models\Office;

it('creates an office with a name', function () {
    $office = Office::factory()->create(['name' => 'テスト社労士事務所']);

    expect($office->name)->toBe('テスト社労士事務所')
        ->and($office->contract_plan)->toBeNull();
});

it('allows contract_plan to be null by default', function () {
    $office = Office::factory()->create();

    expect($office->contract_plan)->toBeNull();
});
