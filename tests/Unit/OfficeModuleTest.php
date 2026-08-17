<?php

use App\Enums\Module;
use App\Models\Office;

test('hasModule returns true for every module when enabled_modules is null (legacy offices)', function () {
    $office = Office::factory()->make(['enabled_modules' => null]);

    foreach (Module::cases() as $module) {
        expect($office->hasModule($module))->toBeTrue();
    }
});

test('hasModule only returns true for modules present in the explicit list', function () {
    $office = Office::factory()->make(['enabled_modules' => ['calc_assistant']]);

    expect($office->hasModule(Module::CalcAssistant))->toBeTrue()
        ->and($office->hasModule(Module::WebPush))->toBeFalse();
});

test('hasModule returns false for every module when the list is empty', function () {
    $office = Office::factory()->make(['enabled_modules' => []]);

    foreach (Module::cases() as $module) {
        expect($office->hasModule($module))->toBeFalse();
    }
});

test('enabledModuleKeys reflects hasModule for every case', function () {
    $office = Office::factory()->make(['enabled_modules' => ['calc_assistant', 'web_push']]);

    expect($office->enabledModuleKeys())->toEqualCanonicalizing(['calc_assistant', 'web_push']);
});
