<?php

declare(strict_types=1);

use Flatpack\Schema\CompositionSchemaKeysGenerator;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Tests\TestCase;
use Illuminate\Support\Facades\File;

uses(TestCase::class);

it('keeps generated CompositionSchemaKeys aligned with the JSON schema files', function (): void {
    $root = dirname(__DIR__, 2);
    $generator = new CompositionSchemaKeysGenerator;
    $form = json_decode(File::get($root . '/resources/schema/form.json'), true, flags: JSON_THROW_ON_ERROR);
    $list = json_decode(File::get($root . '/resources/schema/list.json'), true, flags: JSON_THROW_ON_ERROR);
    $dashboard = json_decode(File::get($root . '/resources/schema/dashboard.json'), true, flags: JSON_THROW_ON_ERROR);
    assert(is_array($form));
    assert(is_array($list));
    assert(is_array($dashboard));

    $sets = $generator->extractKeySets($form, $list, $dashboard);

    expect($sets['formRootPropertyKeys'])->toBe(CompositionSchemaKeys::FORM_ROOT_PROPERTY_KEYS)
        ->and($sets['listRootPropertyKeys'])->toBe(CompositionSchemaKeys::LIST_ROOT_PROPERTY_KEYS)
        ->and($sets['formFieldTypesCanonical'])->toBe(CompositionSchemaKeys::FORM_FIELD_TYPES_CANONICAL)
        ->and($sets['headerActionEntryKeys'])->toBe(CompositionSchemaKeys::HEADER_ACTION_ENTRY_KEYS)
        ->and($sets['listBulkActionEntryKeys'])->toBe(CompositionSchemaKeys::LIST_BULK_ACTION_ENTRY_KEYS)
        ->and($sets['listColumnDefinitionPropertyKeys'])->toBe(CompositionSchemaKeys::LIST_COLUMN_DEFINITION_PROPERTY_KEYS)
        ->and($sets['formPresetTypes'])->toBe(CompositionSchemaKeys::FORM_PRESET_TYPES)
        ->and($sets['successRedirectValues'])->toBe(CompositionSchemaKeys::SUCCESS_REDIRECT_VALUES)
        ->and($sets['listColumnYamlTypes'])->toBe(CompositionSchemaKeys::LIST_COLUMN_YAML_TYPES)
        ->and($sets['buttonVariantValues'])->toBe(CompositionSchemaKeys::BUTTON_VARIANT_VALUES)
        ->and($sets['buttonVariantUiValues'])->toBe(CompositionSchemaKeys::BUTTON_VARIANT_UI_VALUES)
        ->and($sets['optionStatusValues'])->toBe(CompositionSchemaKeys::OPTION_STATUS_VALUES)
        ->and($sets['widgetStatusValues'])->toBe(CompositionSchemaKeys::WIDGET_STATUS_VALUES)
        ->and($sets['listFilterTypes'])->toBe(CompositionSchemaKeys::LIST_FILTER_TYPES)
        ->and($sets['listFilterDateModes'])->toBe(CompositionSchemaKeys::LIST_FILTER_DATE_MODES)
        ->and($sets['listColumnGenericYamlTypes'])->toBe(CompositionSchemaKeys::LIST_COLUMN_GENERIC_YAML_TYPES)
        ->and($sets['listColumnActionButtonEntryKeys'])->toBe(CompositionSchemaKeys::LIST_COLUMN_ACTION_BUTTON_ENTRY_KEYS);
});
