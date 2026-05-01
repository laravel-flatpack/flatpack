<?php

declare(strict_types=1);

use Flatpack\Schema\CompositionSchemaKeysGenerator;
use Flatpack\Tests\TestCase;
use Illuminate\Support\Facades\File;

uses(TestCase::class);

it('keeps generated TypeScript composition schema keys aligned with schema files', function (): void {
    $root = dirname(__DIR__, 2);
    $generator = new CompositionSchemaKeysGenerator;
    $form = json_decode(File::get($root . '/resources/schema/form.json'), true, flags: JSON_THROW_ON_ERROR);
    $list = json_decode(File::get($root . '/resources/schema/list.json'), true, flags: JSON_THROW_ON_ERROR);
    assert(is_array($form));
    assert(is_array($list));

    $sets = $generator->extractKeySets($form, $list);
    $actual = File::get($root . '/resources/js/lib/generated/composition-schema-keys.ts');

    $extractTuple = static function (string $name) use ($actual): array {
        if (preg_match('/export const ' . preg_quote($name, '/') . '\s*=\s*\[(.*?)\]\s*as const;/s', $actual, $matches) !== 1) {
            throw new RuntimeException("Missing TS tuple export: {$name}");
        }
        preg_match_all('/[\'"]([^\'"]+)[\'"]/', $matches[1], $parts);

        return $parts[1];
    };

    $extractListRoot = static function () use ($actual): array {
        if (preg_match('/export const LIST_ROOT = \{(.*?)\}\s*as const;/s', $actual, $matches) !== 1) {
            throw new RuntimeException('Missing TS LIST_ROOT export');
        }
        preg_match_all('/([a-zA-Z0-9_]+)\s*:\s*[\'"]([^\'"]+)[\'"]/', $matches[1], $pairs, PREG_SET_ORDER);
        $out = [];
        foreach ($pairs as $pair) {
            $out[$pair[1]] = $pair[2];
        }

        return $out;
    };
    $extractStringConst = static function (string $name) use ($actual): string {
        if (preg_match('/export const ' . preg_quote($name, '/') . '\s*=\s*[\'"]([^\'"]+)[\'"]\s*as const;/', $actual, $matches) !== 1) {
            throw new RuntimeException("Missing TS string const export: {$name}");
        }

        return $matches[1];
    };

    $expectedListRoot = array_combine(
        $sets['listRootPropertyKeys'],
        $sets['listRootPropertyKeys'],
    );
    expect($extractTuple('FORM_ROOT_PROPERTY_KEYS'))->toBe($sets['formRootPropertyKeys'])
        ->and($extractListRoot())->toBe($expectedListRoot === false ? [] : $expectedListRoot)
        ->and($extractTuple('LIST_ROOT_PROPERTY_KEYS'))->toBe($sets['listRootPropertyKeys'])
        ->and($extractStringConst('FORM_DEFAULT_FIELD_TYPE'))->toBe($sets['formDefaultFieldType'])
        ->and($extractTuple('FORM_FIELD_TYPES_CANONICAL'))->toBe($sets['formFieldTypesCanonical'])
        ->and($extractTuple('HEADER_ACTION_ENTRY_KEYS'))->toBe($sets['headerActionEntryKeys'])
        ->and($extractTuple('LIST_BULK_ACTION_ENTRY_KEYS'))->toBe($sets['listBulkActionEntryKeys'])
        ->and($extractTuple('LIST_COLUMN_ACTION_BUTTON_ENTRY_KEYS'))->toBe($sets['listColumnActionButtonEntryKeys'])
        ->and($extractTuple('LIST_COLUMN_DEFINITION_PROPERTY_KEYS'))->toBe($sets['listColumnDefinitionPropertyKeys'])
        ->and($extractTuple('FORM_PRESET_TYPES'))->toBe($sets['formPresetTypes'])
        ->and($extractTuple('SUCCESS_REDIRECT_VALUES'))->toBe($sets['successRedirectValues'])
        ->and($extractTuple('LIST_COLUMN_YAML_TYPES'))->toBe($sets['listColumnYamlTypes'])
        ->and($extractTuple('LIST_COLUMN_GENERIC_YAML_TYPES'))->toBe($sets['listColumnGenericYamlTypes'])
        ->and($extractTuple('BUTTON_VARIANT_VALUES'))->toBe($sets['buttonVariantValues'])
        ->and($extractTuple('BUTTON_VARIANT_UI_VALUES'))->toBe($sets['buttonVariantUiValues'])
        ->and($extractTuple('OPTION_STATUS_VALUES'))->toBe($sets['optionStatusValues'])
        ->and($extractTuple('WIDGET_STATUS_VALUES'))->toBe($sets['widgetStatusValues'])
        ->and($extractTuple('LIST_FILTER_TYPES'))->toBe($sets['listFilterTypes'])
        ->and($extractTuple('LIST_FILTER_DATE_MODES'))->toBe($sets['listFilterDateModes']);
});
