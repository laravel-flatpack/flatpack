<?php

declare(strict_types=1);

namespace Flatpack\Support;

final class ValidationMessages
{
    public static function required(string $field): string
    {
        return sprintf('%s is required.', str_replace('_', ' ', ucfirst($field)));
    }
}
