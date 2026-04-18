<?php

declare(strict_types=1);

namespace Flatpack\Support;

/**
 * Emits debug messages for unknown keys inside string-keyed YAML object blocks (e.g. {@code actions.publish}).
 */
final class CompositionNestedKeyWarnings
{
    /**
     * @param  list<string>  $knownKeys
     */
    public static function forStringKeyedBlocks(
        mixed $blocks,
        array $knownKeys,
        string $blockName,
        CompositionDebugLog $debug,
    ): void {
        if (! is_array($blocks)) {
            return;
        }

        foreach ($blocks as $entryId => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $id = is_string($entryId) && $entryId !== '' ? $entryId : 'entry';
            $keys = array_keys($definition);
            sort($keys, SORT_STRING);

            foreach ($keys as $key) {
                if (! in_array($key, $knownKeys, true)) {
                    $debug->add(sprintf(
                        'Unknown key "%s" under %s.%s (ignored at runtime).',
                        $key,
                        $blockName,
                        $id,
                    ));
                }
            }
        }
    }
}
