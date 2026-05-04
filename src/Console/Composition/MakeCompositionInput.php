<?php

declare(strict_types=1);

namespace Flatpack\Console\Composition;

/**
 * Normalized inputs for composition generation.
 */
final readonly class MakeCompositionInput
{
    public function __construct(
        public string $modelClass,
        public string $modelBasename,
        public string $entity,
        public string $entities,
        public string $menu,
        public string $icon,
        public int $navOrder,
        public bool $includeBasicActions,
        public bool $includeBulkDelete,
        public bool $includeAutoFields,
        public bool $includeAutoColumns,
        public bool $includeSoftDeleteActions,
        public bool $force,
    ) {}
}
