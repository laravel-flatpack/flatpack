<?php

declare(strict_types=1);

namespace Flatpack\Schema;

/**
 * Allowed {@code preset.type} values for text/textarea fields in form YAML.
 *
 * @see \Flatpack\Support\FormSchemaNormalizer
 */
final class FormFieldPresetType
{
    /**
     * @var list<string>
     */
    public const array ALLOWED = [
        'exact',
        'slug',
        'url',
        'camel',
        'file',
    ];
}
