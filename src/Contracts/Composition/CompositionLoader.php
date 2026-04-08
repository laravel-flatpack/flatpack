<?php

declare(strict_types=1);

namespace Flatpack\Contracts\Composition;

interface CompositionLoader
{
    /**
     * Load and parse a composition file for an entity.
     *
     * @param  string  $entity  Directory name under the compositions path (e.g. "posts")
     * @param  string  $type  File stem (e.g. "form", "list")
     * @return array<string, mixed>
     *
     * @throws CompositionNotFoundException
     */
    public function load(string $entity, string $type): array;
}
