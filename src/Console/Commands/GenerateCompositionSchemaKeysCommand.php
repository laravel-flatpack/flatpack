<?php

declare(strict_types=1);

namespace Flatpack\Console\Commands;

use Flatpack\Schema\CompositionSchemaKeysGenerator;
use Flatpack\Schema\Generated\CompositionSchemaKeys as SchemaKeys;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

final class GenerateCompositionSchemaKeysCommand extends Command
{
    public const string OUTPUT_PHP_RELATIVE = 'src/Schema/Generated/CompositionSchemaKeys.php';

    /** Minimal TS module so the client bundle does not import full JSON Schema files. */
    public const string OUTPUT_TS_RELATIVE = 'resources/js/lib/generated/composition-schema-keys.ts';

    protected $signature = 'flatpack:generate-composition-schema-keys
                            {--check : Exit with status 1 if generated PHP/TS files would change}';

    protected $description = 'Regenerate CompositionSchemaKeys (PHP) and composition-schema-keys.ts from resources/schema/*.json.';

    protected $hidden = true;

    public function handle(CompositionSchemaKeysGenerator $generator): int
    {
        $root = dirname(__DIR__, 3);
        $formPath = $root . '/resources/schema/form.json';
        $listPath = $root . '/resources/schema/list.json';
        $outPhp = $root . '/' . self::OUTPUT_PHP_RELATIVE;
        $outTs = $root . '/' . self::OUTPUT_TS_RELATIVE;

        if (! is_file($formPath) || ! is_file($listPath)) {
            $this->error('Schema JSON files are missing (expected resources/schema/form.json and list.json).');

            return self::FAILURE;
        }

        try {
            $form = $this->decodeJsonFile($formPath);
            $list = $this->decodeJsonFile($listPath);
        } catch (Throwable $e) {
            $this->error('Failed to read schema JSON: ' . $e->getMessage());

            return self::FAILURE;
        }

        $expected = $generator->extractKeySets($form, $list);

        if ($this->option('check')) {
            if (! $this->generatedPhpMatches($expected)) {
                $this->error('CompositionSchemaKeys.php is out of sync with resources/schema/*.json. Run: php artisan flatpack:generate-composition-schema-keys');

                return self::FAILURE;
            }
            $tsContent = $this->formatTypeScriptWithBiomeIfPossible(
                $root,
                $generator->generateTypeScriptModule($form, $list),
            );
            $existingTs = is_file($outTs) ? File::get($outTs) : null;
            if ($existingTs !== $tsContent) {
                $this->error('resources/js/lib/generated/composition-schema-keys.ts is out of sync with resources/schema/*.json. Run: php artisan flatpack:generate-composition-schema-keys');

                return self::FAILURE;
            }
            $this->info('Generated schema keys are up to date:');
            $this->line(' - src/Schema/Generated/CompositionSchemaKeys.php');
            $this->line(' - resources/js/lib/generated/composition-schema-keys.ts');

            return self::SUCCESS;
        }

        $content = $generator->generate($form, $list);
        File::ensureDirectoryExists(dirname($outPhp));
        File::put($outPhp, $content);
        $this->maybeRunPint($root, $outPhp);

        $tsOut = $this->formatTypeScriptWithBiomeIfPossible(
            $root,
            $generator->generateTypeScriptModule($form, $list),
        );
        File::ensureDirectoryExists(dirname($outTs));
        File::put($outTs, $tsOut);

        $this->info('Wrote ' . $outPhp);
        $this->info('Wrote ' . $outTs);

        return self::SUCCESS;
    }

    /**
     * @param  array{
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
     * }  $expected
     */
    private function generatedPhpMatches(array $expected): bool
    {
        return $expected['formRootPropertyKeys'] === SchemaKeys::FORM_ROOT_PROPERTY_KEYS
            && $expected['listRootPropertyKeys'] === SchemaKeys::LIST_ROOT_PROPERTY_KEYS
            && $expected['formFieldTypesCanonical'] === SchemaKeys::FORM_FIELD_TYPES_CANONICAL
            && $expected['headerActionEntryKeys'] === SchemaKeys::HEADER_ACTION_ENTRY_KEYS
            && $expected['listBulkActionEntryKeys'] === SchemaKeys::LIST_BULK_ACTION_ENTRY_KEYS
            && $expected['listColumnDefinitionPropertyKeys'] === SchemaKeys::LIST_COLUMN_DEFINITION_PROPERTY_KEYS
            && $expected['formPresetTypes'] === SchemaKeys::FORM_PRESET_TYPES
            && $expected['successRedirectValues'] === SchemaKeys::SUCCESS_REDIRECT_VALUES
            && $expected['listColumnYamlTypes'] === SchemaKeys::LIST_COLUMN_YAML_TYPES
            && $expected['buttonVariantValues'] === SchemaKeys::BUTTON_VARIANT_VALUES
            && $expected['buttonVariantUiValues'] === SchemaKeys::BUTTON_VARIANT_UI_VALUES
            && $expected['optionStatusValues'] === SchemaKeys::OPTION_STATUS_VALUES
            && $expected['listFilterTypes'] === SchemaKeys::LIST_FILTER_TYPES
            && $expected['listFilterDateModes'] === SchemaKeys::LIST_FILTER_DATE_MODES
            && $expected['listColumnGenericYamlTypes'] === SchemaKeys::LIST_COLUMN_GENERIC_YAML_TYPES
            && $expected['listColumnActionButtonEntryKeys'] === SchemaKeys::LIST_COLUMN_ACTION_BUTTON_ENTRY_KEYS;
    }

    private function maybeRunPint(string $packageRoot, string $outPath): void
    {
        $pint = $packageRoot . '/vendor/bin/pint';
        if (! is_file($pint)) {
            return;
        }

        $command = sprintf(
            'cd %s && %s %s %s',
            escapeshellarg($packageRoot),
            escapeshellarg(PHP_BINARY),
            escapeshellarg($pint),
            escapeshellarg($outPath)
        );

        @shell_exec($command . ' 2>&1');
    }

    /**
     * Formats generated TS with Biome when available so committed files match {@see --check}.
     */
    private function formatTypeScriptWithBiomeIfPossible(string $packageRoot, string $content): string
    {
        $biome = $packageRoot . '/node_modules/.bin/biome';
        if (! is_file($biome)) {
            return $content;
        }

        $relativeTmp = 'resources/js/lib/generated/__flatpack_biome_fmt__.ts';
        $tmp = $packageRoot . '/' . $relativeTmp;
        File::ensureDirectoryExists(dirname($tmp));
        File::put($tmp, $content);
        $command = sprintf(
            'cd %s && %s format --write %s 2>&1',
            escapeshellarg($packageRoot),
            escapeshellarg($biome),
            escapeshellarg($relativeTmp),
        );
        @shell_exec($command);
        if (! is_file($tmp)) {
            return $content;
        }
        $formatted = File::get($tmp);
        File::delete($tmp);

        return $formatted;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $raw = File::get($path);
        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new RuntimeException('Schema root must be a JSON object: ' . $path);
        }

        return $data;
    }
}
