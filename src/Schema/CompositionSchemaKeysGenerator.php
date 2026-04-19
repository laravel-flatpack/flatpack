<?php

declare(strict_types=1);

namespace Flatpack\Schema;

use RuntimeException;

/**
 * Builds {@see Generated\CompositionSchemaKeys} from decoded JSON Schema documents.
 */
final class CompositionSchemaKeysGenerator
{
    /**
     * YAML field types that are accepted in YAML but normalized before runtime checks (see schema description).
     *
     * @var list<string>
     */
    private const array YAML_ONLY_FORM_FIELD_TYPE_ALIASES = ['date'];

    /**
     * Basenames of list.json $defs for each columnDefinition oneOf branch (e.g. columnSelect, columnRelation).
     * When you add a new list column variant, add the corresponding $defs name here so LIST_COLUMN_DEFINITION_PROPERTY_KEYS stays complete.
     *
     * @var list<string>
     */
    private const array LIST_COLUMN_VARIANT_DEF_NAMES = [
        'columnSelect',
        'columnRelation',
        'columnActions',
        'columnDate',
        'columnGeneric',
    ];

    /**
     * Key lists derived from decoded JSON Schema (used by tests and {@see generate()}).
     *
     * @return array{
     *     formRootPropertyKeys: list<string>,
     *     listRootPropertyKeys: list<string>,
     *     formFieldTypesCanonical: list<string>,
     *     headerActionEntryKeys: list<string>,
     *     listBulkActionEntryKeys: list<string>,
     *     listColumnDefinitionPropertyKeys: list<string>,
     *     formPresetTypes: list<string>,
     *     successRedirectValues: list<string>,
     *     listColumnYamlTypes: list<string>,
     *     buttonVariantValues: list<string>,
     *     buttonVariantUiValues: list<string>,
     *     optionStatusValues: list<string>,
     *     listFilterTypes: list<string>,
     *     listFilterDateModes: list<string>,
     *     listColumnGenericYamlTypes: list<string>,
     *     listColumnActionButtonEntryKeys: list<string>,
     * }
     */
    public function extractKeySets(array $formSchema, array $listSchema): array
    {
        $formRoot = self::sortedStringKeys($formSchema['properties'] ?? []);
        $listRoot = self::sortedStringKeys($listSchema['properties'] ?? []);

        $formDefs = $formSchema['$defs'] ?? [];
        $listDefs = $listSchema['$defs'] ?? [];

        $formSuccess = self::successRedirectStringTargets($formDefs['successRedirect'] ?? null);
        $listSuccess = self::successRedirectStringTargets($listDefs['successRedirect'] ?? null);
        self::assertEnumListsMatch('successRedirect', $formSuccess, $listSuccess);

        $formButton = self::enumStrings($formDefs['buttonVariant']['enum'] ?? null);
        $listButton = self::enumStrings($listDefs['buttonVariant']['enum'] ?? null);
        self::assertEnumListsMatch('buttonVariant', $formButton, $listButton);

        $formOptionStatus = self::enumStrings($formDefs['optionStatus']['enum'] ?? null);
        $listOptionStatus = self::enumStrings($listDefs['optionStatus']['enum'] ?? null);
        self::assertEnumListsMatch('optionStatus', $formOptionStatus, $listOptionStatus);

        $presetTypes = self::enumStrings($formDefs['preset']['properties']['type']['enum'] ?? null);

        $listColumnYamlTypes = self::enumStrings($listDefs['listColumnType']['enum'] ?? null);

        $listColumnGenericYamlTypes = self::enumStrings($listDefs['columnGeneric']['properties']['type']['enum'] ?? null);

        $listColumnActionButtonEntryKeys = self::propertyKeysSorted($listDefs['columnActionButton'] ?? []);

        $buttonVariantUiValues = self::sortedStringList(array_values(array_filter(
            $formButton,
            static fn (string $v): bool => $v !== 'primary',
        )));

        $filterSelectConst = $listDefs['filterSelect']['properties']['type']['const'] ?? null;
        $filterDateConst = $listDefs['filterDate']['properties']['type']['const'] ?? null;
        $listFilterTypes = self::sortedStringList(array_values(array_filter([
            is_string($filterSelectConst) ? $filterSelectConst : null,
            is_string($filterDateConst) ? $filterDateConst : null,
        ])));

        $listFilterDateModes = self::enumStrings($listDefs['filterDate']['properties']['mode']['enum'] ?? null);

        $yamlTypes = $formDefs['yamlFormFieldType']['enum'] ?? [];
        $yamlTypes = is_array($yamlTypes) ? $yamlTypes : [];
        $canonicalTypes = self::sortedStringList(array_values(array_diff(
            array_map(strval(...), $yamlTypes),
            self::YAML_ONLY_FORM_FIELD_TYPE_ALIASES,
        )));

        $formHeaderKeys = self::propertyKeysSorted($formDefs['headerActionDefinition'] ?? []);
        $listHeaderKeys = self::propertyKeysSorted($listDefs['headerActionEntry'] ?? []);
        $headerUnion = self::sortedStringList(array_values(array_unique([...$formHeaderKeys, ...$listHeaderKeys])));

        $bulkKeys = self::propertyKeysSorted($listDefs['bulkActionDefinition'] ?? []);

        $columnUnion = [];
        foreach (self::LIST_COLUMN_VARIANT_DEF_NAMES as $defName) {
            $columnUnion = [...$columnUnion, ...self::propertyKeysSorted($listDefs[$defName] ?? [])];
        }
        $columnUnion = self::sortedStringList(array_values(array_unique($columnUnion)));

        return [
            'formRootPropertyKeys' => $formRoot,
            'listRootPropertyKeys' => $listRoot,
            'formFieldTypesCanonical' => $canonicalTypes,
            'headerActionEntryKeys' => $headerUnion,
            'listBulkActionEntryKeys' => $bulkKeys,
            'listColumnDefinitionPropertyKeys' => $columnUnion,
            'formPresetTypes' => $presetTypes,
            'successRedirectValues' => $formSuccess,
            'listColumnYamlTypes' => $listColumnYamlTypes,
            'buttonVariantValues' => $formButton,
            'buttonVariantUiValues' => $buttonVariantUiValues,
            'optionStatusValues' => $formOptionStatus,
            'listFilterTypes' => $listFilterTypes,
            'listFilterDateModes' => $listFilterDateModes,
            'listColumnGenericYamlTypes' => $listColumnGenericYamlTypes,
            'listColumnActionButtonEntryKeys' => $listColumnActionButtonEntryKeys,
        ];
    }

    /**
     * @param  array<string, mixed>  $formSchema
     * @param  array<string, mixed>  $listSchema
     */
    public function generate(array $formSchema, array $listSchema): string
    {
        $keys = $this->extractKeySets($formSchema, $listSchema);
        $formRoot = $keys['formRootPropertyKeys'];
        $listRoot = $keys['listRootPropertyKeys'];
        $canonicalTypes = $keys['formFieldTypesCanonical'];
        $headerUnion = $keys['headerActionEntryKeys'];
        $bulkKeys = $keys['listBulkActionEntryKeys'];
        $columnUnion = $keys['listColumnDefinitionPropertyKeys'];
        $formPresetTypes = $keys['formPresetTypes'];
        $successRedirectValues = $keys['successRedirectValues'];
        $listColumnYamlTypes = $keys['listColumnYamlTypes'];
        $buttonVariantValues = $keys['buttonVariantValues'];
        $buttonVariantUiValues = $keys['buttonVariantUiValues'];
        $optionStatusValues = $keys['optionStatusValues'];
        $listFilterTypes = $keys['listFilterTypes'];
        $listFilterDateModes = $keys['listFilterDateModes'];
        $listColumnGenericYamlTypes = $keys['listColumnGenericYamlTypes'];

        $header = <<<'PHP'
<?php

declare(strict_types=1);

namespace Flatpack\Schema\Generated;

PHP;
        $header .= "\n";
        $header .= '/**' . "\n";
        $header .= ' * AUTO-GENERATED FILE — do not edit by hand.' . "\n";
        $header .= ' *' . "\n";
        $header .= ' * Regenerate with: `php artisan flatpack:generate-composition-schema-keys`' . "\n";
        $header .= ' * Source: resources/schema/form.json, resources/schema/list.json' . "\n";
        $header .= ' */' . "\n";
        $header .= 'final class CompositionSchemaKeys' . "\n";
        $header .= '{' . "\n";

        $body = '';
        $body .= self::constBlock('Top-level keys from form.json `properties` (entity form.yaml).', 'FORM_ROOT_PROPERTY_KEYS', $formRoot);
        $body .= self::listRootAssocPhp($listRoot);
        $body .= self::defaultListRowReorderColumnPhp();
        $body .= self::constBlock('Top-level keys from list.json `properties` (entity list.yaml). Same names as keys of `LIST_ROOT`, sorted.', 'LIST_ROOT_PROPERTY_KEYS', $listRoot);
        $body .= self::constBlock('Canonical field types after YAML aliases are stripped (see yamlFormFieldType enum minus date).', 'FORM_FIELD_TYPES_CANONICAL', $canonicalTypes);
        $body .= self::constBlock('Union of nested keys allowed on toolbar/header action entries (form headerActionDefinition ∪ list headerActionEntry).', 'HEADER_ACTION_ENTRY_KEYS', $headerUnion);
        $body .= self::constBlock('Nested keys for each bulk_actions entry (list.json bulkActionDefinition).', 'LIST_BULK_ACTION_ENTRY_KEYS', $bulkKeys);
        $body .= self::constBlock('Nested keys for each list column `actions` button (list.json columnActionButton).', 'LIST_COLUMN_ACTION_BUTTON_ENTRY_KEYS', $keys['listColumnActionButtonEntryKeys']);
        $body .= self::constBlock('Union of property keys across list columnDefinition oneOf variants.', 'LIST_COLUMN_DEFINITION_PROPERTY_KEYS', $columnUnion);
        $body .= self::constBlock('form.json `$defs.preset.properties.type` enum (field preset kinds).', 'FORM_PRESET_TYPES', $formPresetTypes);
        $body .= self::constBlock('form.json / list.json `$defs.successRedirect` enum.', 'SUCCESS_REDIRECT_VALUES', $successRedirectValues);
        $body .= self::constBlock('list.json `$defs.listColumnType` enum (raw YAML column types).', 'LIST_COLUMN_YAML_TYPES', $listColumnYamlTypes);
        $body .= self::constBlock('list.json `$defs.columnGeneric.properties.type` enum (text-like column kinds when type is set).', 'LIST_COLUMN_GENERIC_YAML_TYPES', $listColumnGenericYamlTypes);
        $body .= self::constBlock('form.json `$defs.buttonVariant` enum (includes YAML alias `primary`).', 'BUTTON_VARIANT_VALUES', $buttonVariantValues);
        $body .= self::constBlock('Button variants after normalizing `primary` → `default` (shadcn / runtime output).', 'BUTTON_VARIANT_UI_VALUES', $buttonVariantUiValues);
        $body .= self::constBlock('form.json `$defs.optionStatus` enum (select/column option status).', 'OPTION_STATUS_VALUES', $optionStatusValues);
        $body .= self::constBlock('list.json filterSelect / filterDate `type` const values.', 'LIST_FILTER_TYPES', $listFilterTypes);
        $body .= self::constBlock('list.json `$defs.filterDate.properties.mode` enum.', 'LIST_FILTER_DATE_MODES', $listFilterDateModes);

        return $header . $body . '}' . "\n";
    }

    /**
     * Minimal TypeScript module for the client bundle (does not embed full JSON Schema files).
     *
     * @param  array<string, mixed>  $formSchema
     * @param  array<string, mixed>  $listSchema
     */
    public function generateTypeScriptModule(array $formSchema, array $listSchema): string
    {
        $keys = $this->extractKeySets($formSchema, $listSchema);

        $out = '';
        $out .= "/**\n";
        $out .= " * AUTO-GENERATED FILE — do not edit by hand.\n";
        $out .= " *\n";
        $out .= " * Regenerate with: `php artisan flatpack:generate-composition-schema-keys`\n";
        $out .= " * Source: resources/schema/form.json, resources/schema/list.json\n";
        $out .= " *\n";
        $out .= " * Intentionally small so the client bundle does not embed full schema JSON.\n";
        $out .= " */\n\n";
        $out .= self::tsConstAsConst(
            'Top-level form.yaml keys from form.json `properties`.',
            'FORM_ROOT_PROPERTY_KEYS',
            $keys['formRootPropertyKeys'],
        );
        $out .= self::listRootObjectTs($keys['listRootPropertyKeys']);
        $out .= self::defaultListRowReorderColumnTs();
        $out .= self::tsConstAsConst(
            'Top-level list.yaml keys from list.json `properties`. Same names as keys of `LIST_ROOT`, sorted.',
            'LIST_ROOT_PROPERTY_KEYS',
            $keys['listRootPropertyKeys'],
        );
        $out .= self::tsConstAsConst(
            'Canonical field types (yamlFormFieldType minus date). Mirrors PHP `CompositionSchemaKeys::FORM_FIELD_TYPES_CANONICAL`.',
            'FORM_FIELD_TYPES_CANONICAL',
            $keys['formFieldTypesCanonical'],
        );
        $out .= self::tsConstAsConst(
            'Union of form/list header action entry property keys.',
            'HEADER_ACTION_ENTRY_KEYS',
            $keys['headerActionEntryKeys'],
        );
        $out .= self::tsConstAsConst(
            'bulk_actions entry keys (list.json bulkActionDefinition).',
            'LIST_BULK_ACTION_ENTRY_KEYS',
            $keys['listBulkActionEntryKeys'],
        );
        $out .= self::tsConstAsConst(
            'List column row action buttons (list.json columnActionButton). Mirrors PHP `CompositionSchemaKeys::LIST_COLUMN_ACTION_BUTTON_ENTRY_KEYS`.',
            'LIST_COLUMN_ACTION_BUTTON_ENTRY_KEYS',
            $keys['listColumnActionButtonEntryKeys'],
        );
        $out .= self::tsConstAsConst(
            'Union of list columnDefinition variant property keys.',
            'LIST_COLUMN_DEFINITION_PROPERTY_KEYS',
            $keys['listColumnDefinitionPropertyKeys'],
        );
        $out .= self::tsConstAsConst(
            'Field preset kinds (form.json preset.type enum). Mirrors PHP `CompositionSchemaKeys::FORM_PRESET_TYPES`.',
            'FORM_PRESET_TYPES',
            $keys['formPresetTypes'],
        );
        $out .= self::tsConstAsConst(
            'success_redirect enum. Mirrors PHP `CompositionSchemaKeys::SUCCESS_REDIRECT_VALUES`.',
            'SUCCESS_REDIRECT_VALUES',
            $keys['successRedirectValues'],
        );
        $out .= self::tsConstAsConst(
            'Raw list column types (list.json listColumnType). Mirrors PHP `CompositionSchemaKeys::LIST_COLUMN_YAML_TYPES`.',
            'LIST_COLUMN_YAML_TYPES',
            $keys['listColumnYamlTypes'],
        );
        $out .= self::tsConstAsConst(
            'Generic list column type enum (columnGeneric.type). Mirrors PHP `CompositionSchemaKeys::LIST_COLUMN_GENERIC_YAML_TYPES`.',
            'LIST_COLUMN_GENERIC_YAML_TYPES',
            $keys['listColumnGenericYamlTypes'],
        );
        $out .= self::tsConstAsConst(
            'YAML button variants (includes primary). Mirrors PHP `CompositionSchemaKeys::BUTTON_VARIANT_VALUES`.',
            'BUTTON_VARIANT_VALUES',
            $keys['buttonVariantValues'],
        );
        $out .= self::tsConstAsConst(
            'Runtime/UI button variants (primary stripped). Mirrors PHP `CompositionSchemaKeys::BUTTON_VARIANT_UI_VALUES`.',
            'BUTTON_VARIANT_UI_VALUES',
            $keys['buttonVariantUiValues'],
        );
        $out .= self::tsConstAsConst(
            'Option status values. Mirrors PHP `CompositionSchemaKeys::OPTION_STATUS_VALUES`.',
            'OPTION_STATUS_VALUES',
            $keys['optionStatusValues'],
        );
        $out .= self::tsConstAsConst(
            'List filter discriminator types (filterSelect / filterDate). Mirrors PHP `CompositionSchemaKeys::LIST_FILTER_TYPES`.',
            'LIST_FILTER_TYPES',
            $keys['listFilterTypes'],
        );
        $out .= self::tsConstAsConst(
            'Date filter mode enum. Mirrors PHP `CompositionSchemaKeys::LIST_FILTER_DATE_MODES`.',
            'LIST_FILTER_DATE_MODES',
            $keys['listFilterDateModes'],
        );

        return $out;
    }

    /**
     * @param  list<string>  $strings
     */
    private static function tsConstAsConst(string $doc, string $name, array $strings): string
    {
        $block = '';
        $block .= '/**' . "\n";
        $block .= ' * ' . $doc . "\n";
        $block .= ' */' . "\n";
        $block .= 'export const ' . $name . ' = ';
        $block .= self::exportTsStringTupleAsConst($strings);
        $block .= ";\n\n";

        return $block;
    }

    /**
     * @param  list<string>  $strings
     */
    private static function exportTsStringTupleAsConst(array $strings): string
    {
        if ($strings === []) {
            return '[] as const';
        }

        $parts = array_map(static function (string $s): string {
            $json = json_encode($s, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            return (string) $json;
        }, $strings);

        return "[\n    " . implode(",\n    ", $parts) . ",\n] as const";
    }

    /**
     * @param  array<string, mixed>  $def
     * @return list<string>
     */
    private static function propertyKeysSorted(array $def): array
    {
        $props = $def['properties'] ?? [];

        return self::sortedStringKeys(is_array($props) ? $props : []);
    }

    /**
     * @param  array<string, mixed>  $assoc
     * @return list<string>
     */
    private static function sortedStringKeys(array $assoc): array
    {
        $keys = array_keys($assoc);
        $keys = array_map(strval(...), $keys);
        $keys = array_values(array_unique($keys));
        sort($keys, SORT_STRING);

        return $keys;
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function sortedStringList(array $values): array
    {
        $values = array_values(array_unique($values));
        sort($values, SORT_STRING);

        return $values;
    }

    /**
     * String targets allowed in YAML for {@code success_redirect} (boolean {@code true} is a runtime alias for list, not part of this list).
     *
     * @param  array<string, mixed>|null  $def  JSON Schema {@code $defs.successRedirect} (legacy enum or {@code oneOf} string branch).
     * @return list<string>
     */
    private static function successRedirectStringTargets(?array $def): array
    {
        if ($def === null) {
            return [];
        }

        if (isset($def['enum']) && is_array($def['enum'])) {
            return self::enumStrings($def['enum']);
        }

        if (isset($def['oneOf']) && is_array($def['oneOf'])) {
            $out = [];
            foreach ($def['oneOf'] as $branch) {
                if (! is_array($branch)) {
                    continue;
                }
                if (($branch['type'] ?? null) === 'string' && isset($branch['enum']) && is_array($branch['enum'])) {
                    $out = [...$out, ...self::enumStrings($branch['enum'])];
                }
            }

            return self::sortedStringList($out);
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private static function enumStrings(mixed $enum): array
    {
        if (! is_array($enum)) {
            return [];
        }

        return self::sortedStringList(array_map(strval(...), $enum));
    }

    /**
     * @param  list<string>  $a
     * @param  list<string>  $b
     */
    private static function assertEnumListsMatch(string $name, array $a, array $b): void
    {
        if ($a !== $b) {
            throw new RuntimeException(
                "JSON schema mismatch: {$name} enum differs between form.json and list.json.",
            );
        }
    }

    /**
     * Identity map of list.json root property names (each key maps to itself). Use {@see LIST_ROOT}['columns'] or array access for string tokens without repeating literals.
     *
     * @param  list<string>  $listRoot
     */
    private static function listRootAssocPhp(array $listRoot): string
    {
        $out = '    /**' . "\n";
        $out .= '     * Identity map of list.json root property names.' . "\n";
        $out .= '     * Use `LIST_ROOT[\'columns\']` (or array access with a variable key) for string tokens.' . "\n";
        $out .= '     *' . "\n";
        $out .= '     * @var array<string, string>' . "\n";
        $out .= '     */' . "\n";
        $out .= '    public const array LIST_ROOT = ';
        if ($listRoot === []) {
            $out .= '[];' . "\n\n";

            return $out;
        }

        $lines = [];
        foreach ($listRoot as $key) {
            $lines[] = '        ' . var_export($key, true) . ' => ' . var_export($key, true);
        }
        $out .= "[\n" . implode(",\n", $lines) . ",\n    ];\n\n";

        return $out;
    }

    /**
     * @param  list<string>  $listRoot
     */
    private static function listRootObjectTs(array $listRoot): string
    {
        if ($listRoot === []) {
            return "/** Identity map of list.json root keys (empty). */\nexport const LIST_ROOT = {} as const;\n\nexport type ListRootKey = keyof typeof LIST_ROOT;\n\n";
        }

        $lines = [];
        foreach ($listRoot as $key) {
            $prop = self::tsObjectPropertyName($key);
            $json = json_encode($key, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $lines[] = '    ' . $prop . ': ' . $json;
        }

        $out = '/** Identity map of list.json root property names; use `LIST_ROOT.nav_order`, `LIST_ROOT[\'bulk_actions\']`, etc. */' . "\n";
        $out .= 'export const LIST_ROOT = {' . "\n";
        $out .= implode(",\n", $lines);
        $out .= ",\n} as const;\n\n";
        $out .= 'export type ListRootKey = keyof typeof LIST_ROOT;' . "\n\n";

        return $out;
    }

    /**
     * Row reorder column default (not derived from list.json properties).
     */
    private static function defaultListRowReorderColumnPhp(): string
    {
        $out = '    /**' . "\n";
        $out .= '     * Default database column for row reorder when list.yaml has `reorderable: true`.' . "\n";
        $out .= '     * Not a list.yaml root property; unrelated to sidebar `nav_order`.' . "\n";
        $out .= '     */' . "\n";
        $out .= '    public const string DEFAULT_LIST_ROW_REORDER_COLUMN = \'sort_order\';' . "\n\n";

        return $out;
    }

    /**
     * Row reorder column default (not derived from list.json properties).
     */
    private static function defaultListRowReorderColumnTs(): string
    {
        return "/**\n * Default DB column when list.yaml has `reorderable: true`. Not a list.yaml root key.\n */\nexport const DEFAULT_LIST_ROW_REORDER_COLUMN = 'sort_order' as const;\n\n";
    }

    /**
     * Unquoted object property name when valid in JS; otherwise quoted JSON string.
     */
    private static function tsObjectPropertyName(string $key): string
    {
        if (preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $key) === 1) {
            return $key;
        }

        return json_encode($key, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  list<string>  $strings
     */
    private static function constBlock(string $doc, string $name, array $strings): string
    {
        $out = '    /**' . "\n";
        $out .= '     * ' . $doc . "\n";
        $out .= '     *' . "\n";
        $out .= '     * @var list<string>' . "\n";
        $out .= '     */' . "\n";
        $out .= '    public const array ' . $name . ' = ';
        $out .= self::exportShortArray($strings);
        $out .= ';' . "\n\n";

        return $out;
    }

    /**
     * @param  list<string>  $strings
     */
    private static function exportShortArray(array $strings): string
    {
        if ($strings === []) {
            return '[]';
        }

        $parts = array_map(static fn (string $s): string => var_export($s, true), $strings);

        return "[\n        " . implode(",\n        ", $parts) . ",\n    ]";
    }
}
