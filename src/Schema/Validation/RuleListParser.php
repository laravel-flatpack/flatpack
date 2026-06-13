<?php

declare(strict_types=1);

namespace Flatpack\Schema\Validation;

/**
 * Parses YAML {@code rules} written as a pipe-separated string or list of strings.
 *
 * @internal Shared by form and list schema rule builders.
 */
final class RuleListParser
{
    /**
     * @return list<string|\Illuminate\Contracts\Validation\ValidationRule>
     */
    public static function parse(mixed $raw): array
    {
        if ($raw === null) {
            return [];
        }

        if (is_string($raw)) {
            return array_values(array_filter(array_map(trim(...), explode('|', $raw)), fn (string $s): bool => $s !== ''));
        }

        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return $out;
    }
}
