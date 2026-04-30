<?php

declare(strict_types=1);

namespace Flatpack\Schema;

use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;

final class SchemaDocumentationPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function buildDocument(string $schemaType): array
    {
        $schema = $this->decodeSchemaFile($schemaType);
        $defs = $schema['$defs'] ?? [];
        if (! is_array($defs)) {
            $defs = [];
        }

        $definitions = [];
        foreach ($defs as $definitionKey => $definitionSchema) {
            if (! is_string($definitionKey) || ! is_array($definitionSchema)) {
                continue;
            }

            $definitions[] = $this->normalizeSchemaNode($definitionKey, $definitionSchema);
        }

        usort(
            $definitions,
            static fn (array $a, array $b): int => strcmp(
                (string) ($a['key'] ?? ''),
                (string) ($b['key'] ?? ''),
            ),
        );

        return [
            'id' => $schemaType,
            'title' => is_string($schema['title'] ?? null)
                ? $schema['title']
                : sprintf('Flatpack %s schema', $schemaType),
            'description' => is_string($schema['description'] ?? null)
                ? $schema['description']
                : null,
            'meta' => [
                'propertyCount' => is_array($schema['properties'] ?? null)
                    ? count($schema['properties'])
                    : 0,
                'definitionCount' => count($definitions),
            ],
            'root' => $this->normalizeSchemaNode('root', $schema),
            'definitions' => $definitions,
            'raw' => $schema,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSchemaNode(string $key, array $schema): array
    {
        $required = $schema['required'] ?? [];
        $requiredSet = [];
        if (is_array($required)) {
            foreach ($required as $requiredKey) {
                if (is_string($requiredKey) && $requiredKey !== '') {
                    $requiredSet[$requiredKey] = true;
                }
            }
        }

        $properties = [];
        $rawProperties = $schema['properties'] ?? [];
        if (is_array($rawProperties)) {
            foreach ($rawProperties as $propertyName => $propertySchema) {
                if (! is_string($propertyName) || ! is_array($propertySchema)) {
                    continue;
                }

                $properties[] = $this->normalizePropertyNode(
                    $propertyName,
                    $propertySchema,
                    array_key_exists($propertyName, $requiredSet),
                );
            }
        }

        return [
            'key' => $key,
            'title' => is_string($schema['title'] ?? null) ? $schema['title'] : null,
            'description' => is_string($schema['description'] ?? null) ? $schema['description'] : null,
            'type' => $this->detectTypeLabel($schema),
            'required' => array_keys($requiredSet),
            'properties' => $properties,
            'options' => $this->normalizeEnumValues($schema['enum'] ?? null),
            'const' => $this->stringifyValue($schema['const'] ?? null),
            'validation' => $this->extractValidationConstraints($schema),
            'compositionRules' => $this->extractCompositionRules($schema),
            'ref' => is_string($schema['$ref'] ?? null) ? $schema['$ref'] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePropertyNode(string $name, array $schema, bool $required): array
    {
        return [
            'name' => $name,
            'required' => $required,
            'description' => is_string($schema['description'] ?? null) ? $schema['description'] : null,
            'type' => $this->detectTypeLabel($schema),
            'ref' => is_string($schema['$ref'] ?? null) ? $schema['$ref'] : null,
            'options' => $this->normalizeEnumValues($schema['enum'] ?? null),
            'const' => $this->stringifyValue($schema['const'] ?? null),
            'default' => $this->stringifyValue($schema['default'] ?? null),
            'validation' => $this->extractValidationConstraints($schema),
            'compositionRules' => $this->extractCompositionRules($schema),
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizeEnumValues(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $option) {
            $normalized[] = $this->stringifyValue($option);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, string>
     */
    private function extractValidationConstraints(array $schema): array
    {
        $keys = [
            'minLength',
            'maxLength',
            'minimum',
            'maximum',
            'exclusiveMinimum',
            'exclusiveMaximum',
            'pattern',
            'format',
            'minItems',
            'maxItems',
            'uniqueItems',
            'minProperties',
            'maxProperties',
        ];

        $constraints = [];
        foreach ($keys as $constraintKey) {
            if (! array_key_exists($constraintKey, $schema)) {
                continue;
            }

            $constraints[$constraintKey] = $this->stringifyValue($schema[$constraintKey]);
        }

        return $constraints;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, int>
     */
    private function extractCompositionRules(array $schema): array
    {
        $compositionKeys = ['oneOf', 'anyOf', 'allOf'];

        $rules = [];
        foreach ($compositionKeys as $compositionKey) {
            $value = $schema[$compositionKey] ?? null;
            if (is_array($value)) {
                $rules[$compositionKey] = count($value);
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function detectTypeLabel(array $schema): string
    {
        $type = $schema['type'] ?? null;
        if (is_string($type) && $type !== '') {
            return $type;
        }

        if (is_array($type) && $type !== []) {
            return implode(' | ', array_map(
                static fn (mixed $item): string => is_scalar($item) ? (string) $item : 'unknown',
                $type,
            ));
        }

        if (is_string($schema['$ref'] ?? null)) {
            return '$ref';
        }

        if (array_key_exists('oneOf', $schema)) {
            return 'oneOf';
        }

        if (array_key_exists('anyOf', $schema)) {
            return 'anyOf';
        }

        if (array_key_exists('allOf', $schema)) {
            return 'allOf';
        }

        return 'mixed';
    }

    private function stringifyValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        try {
            return (string) json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException) {
            return '[complex]';
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSchemaFile(string $schemaType): array
    {
        if (! in_array($schemaType, ['form', 'list'], true)) {
            throw new RuntimeException(sprintf('Unsupported schema type "%s".', $schemaType));
        }

        $path = dirname(__DIR__, 2) . '/resources/schema/' . $schemaType . '.json';
        if (! is_file($path)) {
            throw new RuntimeException(sprintf('Schema file not found: %s', $path));
        }

        $payload = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($payload)) {
            throw new RuntimeException(sprintf('Schema root must be a JSON object: %s', $path));
        }

        return $payload;
    }
}
