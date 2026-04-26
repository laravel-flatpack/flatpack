<?php

declare(strict_types=1);

namespace Flatpack\Schema;

use Flatpack\Schema\Forms\Normalization\FormSchemaPipelineState;
use Flatpack\Schema\Forms\Normalization\Pipes\MergeFormTabsIntoFieldsPipe;
use Flatpack\Schema\Lists\Normalization\ListSchemaPipelineState;
use Flatpack\Schema\Lists\Normalization\Pipes\MergeListTabsIntoColumnsPipe;

/**
 * Applies the same tab-flattening transforms as the Inertia normalizers so code paths that
 * read raw YAML (submit validation, save resolution, …) see merged {@code fields} / {@code columns}.
 */
final class CompositionTabsMerge
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    public static function form(?array $schema): ?array
    {
        if ($schema === null) {
            return null;
        }

        if (! isset($schema['tabs']) || ! is_array($schema['tabs']) || $schema['tabs'] === []) {
            return $schema;
        }

        $state = new FormSchemaPipelineState($schema, null);
        (new MergeFormTabsIntoFieldsPipe)->handle($state, fn ($s) => $s);

        return $state->schema;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    public static function list(?array $schema): ?array
    {
        if ($schema === null) {
            return null;
        }

        if (! isset($schema['tabs']) || ! is_array($schema['tabs']) || $schema['tabs'] === []) {
            return $schema;
        }

        $state = new ListSchemaPipelineState($schema, null);
        (new MergeListTabsIntoColumnsPipe)->handle($state, fn ($s) => $s);

        return $state->schema;
    }
}
