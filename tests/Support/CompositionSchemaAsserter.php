<?php

declare(strict_types=1);

namespace Flatpack\Tests\Support;

use JsonSchema\Validator;
use RuntimeException;

/**
 * Validates decoded composition arrays (e.g. from YAML) against resources/schema/*.json.
 */
final class CompositionSchemaAsserter
{
    public static function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function formSchemaPath(): string
    {
        return self::packageRoot() . '/resources/schema/form.json';
    }

    public static function listSchemaPath(): string
    {
        return self::packageRoot() . '/resources/schema/list.json';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    public static function validateAgainstSchema(string $schemaPath, array $data): array
    {
        $raw = file_get_contents($schemaPath);
        if ($raw === false) {
            throw new RuntimeException("Cannot read schema file: {$schemaPath}");
        }

        $schema = json_decode($raw);
        if (! is_object($schema)) {
            throw new RuntimeException("Invalid JSON schema file: {$schemaPath}");
        }

        $payload = json_decode(json_encode($data, JSON_THROW_ON_ERROR));
        $validator = new Validator;
        $validator->validate($payload, $schema);

        return $validator->getErrors();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    public static function validateForm(array $data): array
    {
        return self::validateAgainstSchema(self::formSchemaPath(), $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    public static function validateList(array $data): array
    {
        return self::validateAgainstSchema(self::listSchemaPath(), $data);
    }
}
