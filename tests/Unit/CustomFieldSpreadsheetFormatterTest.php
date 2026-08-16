<?php

use App\Enums\CustomFieldType;
use App\Services\CustomFieldSpreadsheetFormatter;

beforeEach(function () {
    $this->formatter = new CustomFieldSpreadsheetFormatter;
});

it('passes text, number, date and select values through unchanged on export', function () {
    expect($this->formatter->formatForExport(CustomFieldType::Text, 'A-001'))->toBe('A-001')
        ->and($this->formatter->formatForExport(CustomFieldType::Number, '42.5'))->toBe('42.5')
        ->and($this->formatter->formatForExport(CustomFieldType::Date, '2026-04-01'))->toBe('2026-04-01')
        ->and($this->formatter->formatForExport(CustomFieldType::Select, 'プレミアム'))->toBe('プレミアム');
});

it('formats a checkbox value as TRUE/FALSE on export, and null stays null', function () {
    expect($this->formatter->formatForExport(CustomFieldType::Checkbox, true))->toBe('TRUE')
        ->and($this->formatter->formatForExport(CustomFieldType::Checkbox, false))->toBe('FALSE')
        ->and($this->formatter->formatForExport(CustomFieldType::Checkbox, null))->toBeNull()
        ->and($this->formatter->formatForExport(CustomFieldType::Text, null))->toBeNull();
});

it('passes text, number, date and select raw values through unchanged on import', function () {
    expect($this->formatter->parseForImport(CustomFieldType::Text, 'A-001'))->toBe('A-001')
        ->and($this->formatter->parseForImport(CustomFieldType::Number, '42.5'))->toBe('42.5')
        ->and($this->formatter->parseForImport(CustomFieldType::Select, 'プレミアム'))->toBe('プレミアム');
});

it('recognizes common truthy and falsy tokens as a checkbox value on import', function () {
    foreach (['TRUE', 'true', '1', 'はい', '有', '○'] as $token) {
        expect($this->formatter->parseForImport(CustomFieldType::Checkbox, $token))->toBeTrue();
    }

    foreach (['FALSE', 'false', '0', 'いいえ', '無', '×'] as $token) {
        expect($this->formatter->parseForImport(CustomFieldType::Checkbox, $token))->toBeFalse();
    }
});

it('leaves an unrecognized checkbox token untouched so downstream validation rejects it', function () {
    expect($this->formatter->parseForImport(CustomFieldType::Checkbox, 'まあまあ'))->toBe('まあまあ');
});

it('stringifies a numeric Excel cell value for a number field so it matches form-submitted storage', function () {
    expect($this->formatter->parseForImport(CustomFieldType::Number, 3.5))->toBe('3.5')
        ->and($this->formatter->parseForImport(CustomFieldType::Number, 42))->toBe('42')
        ->and($this->formatter->parseForImport(CustomFieldType::Number, '42.5'))->toBe('42.5');
});

it('treats an empty or null checkbox cell as null', function () {
    expect($this->formatter->parseForImport(CustomFieldType::Checkbox, ''))->toBeNull()
        ->and($this->formatter->parseForImport(CustomFieldType::Checkbox, null))->toBeNull();
});
