<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Flatpack\Schema\Generated\CompositionSchemaKeys;

/**
 * Prepares list composition YAML for Inertia / JSON responses.
 */
final class ListSchemaNormalizer
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    public function normalizedListSchema(?array $schema, ?CompositionDebugLog $debug = null): ?array
    {
        if ($schema === null) {
            return null;
        }

        if ($debug !== null) {
            $this->logUnknownTopLevelListKeys($schema, $debug);
            CompositionNestedKeyWarnings::forStringKeyedBlocks(
                $schema['actions'] ?? null,
                CompositionSchemaKeys::HEADER_ACTION_ENTRY_KEYS,
                'actions',
                $debug,
            );
            CompositionNestedKeyWarnings::forStringKeyedBlocks(
                $schema['bulk_actions'] ?? null,
                CompositionSchemaKeys::LIST_BULK_ACTION_ENTRY_KEYS,
                'bulk_actions',
                $debug,
            );
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function logUnknownTopLevelListKeys(array $schema, CompositionDebugLog $debug): void
    {
        $keys = array_keys($schema);
        sort($keys, SORT_STRING);

        foreach ($keys as $key) {
            if (! in_array($key, CompositionSchemaKeys::LIST_ROOT_PROPERTY_KEYS, true)) {
                $debug->add(sprintf('Unknown top-level list key "%s" (ignored at runtime).', $key));
            }
        }
    }
}
