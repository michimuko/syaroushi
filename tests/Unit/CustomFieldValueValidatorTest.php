<?php

use App\Enums\CustomFieldType;
use App\Models\CustomFieldDefinition;
use App\Services\CustomFieldValueValidator;
use Illuminate\Support\Facades\Validator;

function definition(int $id, CustomFieldType $type, ?array $options = null): CustomFieldDefinition
{
    $definition = new CustomFieldDefinition;
    $definition->id = $id;
    $definition->field_type = $type;
    $definition->options = $options;

    return $definition;
}

beforeEach(function () {
    $this->validator = new CustomFieldValueValidator;
});

it('generates a string rule for text fields', function () {
    $rules = $this->validator->rules(collect([definition(1, CustomFieldType::Text)]));

    expect(Validator::make(['custom_fields' => [1 => 'hello']], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['custom_fields' => [1 => str_repeat('a', 1001)]], $rules)->fails())->toBeTrue();
});

it('generates a numeric rule for number fields', function () {
    $rules = $this->validator->rules(collect([definition(2, CustomFieldType::Number)]));

    expect(Validator::make(['custom_fields' => [2 => '12.5']], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['custom_fields' => [2 => 'not-a-number']], $rules)->fails())->toBeTrue();
});

it('generates a date rule for date fields', function () {
    $rules = $this->validator->rules(collect([definition(3, CustomFieldType::Date)]));

    expect(Validator::make(['custom_fields' => [3 => '2026-04-01']], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['custom_fields' => [3 => 'not-a-date']], $rules)->fails())->toBeTrue();
});

it('generates an in-list rule for select fields based on the definition options', function () {
    $rules = $this->validator->rules(collect([definition(4, CustomFieldType::Select, ['A', 'B'])]));

    expect(Validator::make(['custom_fields' => [4 => 'A']], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['custom_fields' => [4 => 'Z']], $rules)->fails())->toBeTrue();
});

it('generates a boolean rule for checkbox fields', function () {
    $rules = $this->validator->rules(collect([definition(5, CustomFieldType::Checkbox)]));

    expect(Validator::make(['custom_fields' => [5 => true]], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['custom_fields' => [5 => 'not-a-bool']], $rules)->fails())->toBeTrue();
});

it('allows null values for every field type (nullable)', function () {
    $rules = $this->validator->rules(collect([
        definition(1, CustomFieldType::Text),
        definition(2, CustomFieldType::Number),
        definition(3, CustomFieldType::Date),
        definition(4, CustomFieldType::Select, ['A']),
        definition(5, CustomFieldType::Checkbox),
    ]));

    $values = ['custom_fields' => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null]];

    expect(Validator::make($values, $rules)->passes())->toBeTrue();
});
