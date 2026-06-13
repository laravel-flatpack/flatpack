<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms\Normalization;

use Flatpack\Support\CompositionDebugLog;

/**
 * Canonicalizes YAML `span` values for form fields and widgets (fraction aliases → named tokens).
 */
final class FieldSpanCanonicalizer
{
    /** @var array<string, string> raw input (trimmed) → canonical named span */
    private const array CANONICAL = [
        'full' => 'full',
        'half' => 'half',
        '1/2' => 'half',
        'two_thirds' => 'two_thirds',
        '2/3' => 'two_thirds',
        'third' => 'third',
        '1/3' => 'third',
        'quarter' => 'quarter',
        '1/4' => 'quarter',
    ];

    public static function canonical(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $trimmed = trim($raw);

        return self::CANONICAL[$trimmed] ?? null;
    }

    /**
     * Mutates {@code $definition}: sets canonical `span` or unsets invalid/missing handling.
     *
     * @param  array<string, mixed>  $definition
     */
    public static function applyToDefinition(
        array &$definition,
        string $debugContext,
        ?CompositionDebugLog $log,
    ): void {
        if (! array_key_exists('span', $definition)) {
            return;
        }

        $canonical = self::canonical($definition['span']);
        if ($canonical === null) {
            unset($definition['span']);
            $log?->add(sprintf(
                '%s: removed invalid span (use full, half|1/2, two_thirds|2/3, third|1/3, quarter|1/4).',
                $debugContext,
            ));

            return;
        }

        $definition['span'] = $canonical;
    }

    /**
     * Copies canonical `span` from source into target when valid; logs when source declares span but invalid.
     *
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $target
     */
    public static function mergeIntoIfPresent(
        array $source,
        array &$target,
        string $debugContext,
        ?CompositionDebugLog $log,
    ): void {
        if (! array_key_exists('span', $source)) {
            return;
        }

        $canonical = self::canonical($source['span']);
        if ($canonical === null) {
            $log?->add(sprintf(
                '%s: ignored invalid span (use full, half|1/2, two_thirds|2/3, third|1/3, quarter|1/4).',
                $debugContext,
            ));

            return;
        }

        $target['span'] = $canonical;
    }
}
