<?php

declare(strict_types=1);

namespace Flatpack\Contracts\Actions;

interface ActionResolver
{
    /**
     * Resolve the handler for a named action (e.g. "save", "delete") for a composition.
     *
     * @param  array<string, mixed>  $composition  Parsed YAML for the current screen
     */
    public function resolve(string $actionName, array $composition): ?FlatpackAction;
}
