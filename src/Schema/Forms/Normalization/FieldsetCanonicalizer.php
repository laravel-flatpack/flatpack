<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms\Normalization;

use Flatpack\Support\CompositionDebugLog;

/**
 * Canonicalizes YAML {@code fieldset} for grouping adjacent fields (string label or {@code { label, icon?, variant?, collapsed? }}).
 *
 * Normalized output is {@code array{label: string, icon?: string, variant?: 'minimal'|'plain'|'none', collapsed?: bool}}.
 * {@code variant} is omitted when {@code card} (default) for stable JSON.
 * {@code collapsed} is omitted when absent or invalid; stripped when {@code variant} is {@code none}.
 */
final class FieldsetCanonicalizer
{
    private const int MAX_LABEL_LENGTH = 200;

    private const int MAX_ICON_LENGTH = 80;

    /** @var list<string> */
    private const array VARIANTS_NON_CARD = ['minimal', 'plain', 'none'];

    /**
     * Mutates {@code $definition}: sets canonical {@code fieldset} array or removes invalid values.
     *
     * @param  array<string, mixed>  $definition
     */
    public static function applyToDefinition(array &$definition, ?CompositionDebugLog $log = null): void
    {
        if (! array_key_exists('fieldset', $definition)) {
            return;
        }

        $raw = $definition['fieldset'];

        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '' || mb_strlen($trimmed) > self::MAX_LABEL_LENGTH) {
                unset($definition['fieldset']);

                return;
            }
            $definition['fieldset'] = [
                'label' => $trimmed,
            ];

            return;
        }

        if (! is_array($raw)) {
            unset($definition['fieldset']);

            return;
        }

        $labelRaw = $raw['label'] ?? null;
        if (! is_string($labelRaw)) {
            unset($definition['fieldset']);

            return;
        }

        $label = trim($labelRaw);
        if ($label === '' || mb_strlen($label) > self::MAX_LABEL_LENGTH) {
            unset($definition['fieldset']);

            return;
        }

        $out = [
            'label' => $label,
        ];

        $iconNormalized = self::normalizeIconKey($raw['icon'] ?? null);
        if ($iconNormalized !== '') {
            $out['icon'] = $iconNormalized;
        }

        $variantNonCard = self::normalizeVariantKey($raw['variant'] ?? null);
        if ($variantNonCard !== null) {
            $out['variant'] = $variantNonCard;
        }

        $collapsedHadKey = array_key_exists('collapsed', $raw);
        if ($collapsedHadKey && is_bool($raw['collapsed'])) {
            $out['collapsed'] = $raw['collapsed'];
        }

        if (($out['variant'] ?? null) === 'none' && array_key_exists('collapsed', $out)) {
            unset($out['collapsed']);
            $log?->add(
                'Fieldset with variant none cannot use collapsed (collapsed key stripped).',
            );
        }

        $definition['fieldset'] = $out;
    }

    /**
     * Returns a non-{@code card} variant to persist, or {@code null} when default/invalid (effective {@code card}).
     */
    private static function normalizeVariantKey(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $t = trim(mb_strtolower($raw));
        if ($t === '' || $t === 'card') {
            return null;
        }

        if (! in_array($t, self::VARIANTS_NON_CARD, true)) {
            return null;
        }

        return $t;
    }

    /**
     * Normalizes icon registry keys to kebab-case (Lucide menu icon names).
     */
    private static function normalizeIconKey(mixed $raw): string
    {
        if (! is_string($raw)) {
            return '';
        }

        $t = trim($raw);
        if ($t === '') {
            return '';
        }

        $t = mb_strtolower(str_replace('_', '-', $t));

        if (mb_strlen($t) > self::MAX_ICON_LENGTH) {
            return '';
        }

        return $t;
    }
}
