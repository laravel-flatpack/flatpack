<?php

declare(strict_types=1);

namespace Flatpack\Contracts\Composition;

interface CompositionQuery
{
    /**
     * Load a composition when present; return null if the file is missing.
     *
     * @return array<string, mixed>|null
     */
    public function optional(string $entity, string $type): ?array;
}
