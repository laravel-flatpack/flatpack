<?php

declare(strict_types=1);

namespace Flatpack\Composition;

use Flatpack\Contracts\Composition\CompositionQuery;

/**
 * Entity-scoped composition data for Flatpack pages (list, form, …).
 */
final readonly class EntityComposition
{
    public function __construct(
        private CompositionQuery $compositions,
        private CompositionValues $values,
    ) {}

    public function listFor(string $entity): ListComposition
    {
        $data = $this->compositions->optional($entity, 'list');

        return new ListComposition(
            name: $this->values->displayName($data),
            model: $this->values->modelClass($data),
            icon: $this->values->icon($data),
            nav_order: $this->values->navOrder($data),
        );
    }

    public function formFor(string $entity): FormComposition
    {
        $data = $this->compositions->optional($entity, 'form');

        return new FormComposition(
            name: $this->values->displayName($data),
            model: $this->values->modelClass($data),
            icon: $this->values->icon($data),
        );
    }

    /**
     * Raw list composition YAML (null if missing).
     *
     * @return array<string, mixed>|null
     */
    public function listSchema(string $entity): ?array
    {
        return $this->compositions->optional($entity, 'list');
    }

    /**
     * Raw form composition YAML (null if missing).
     *
     * @return array<string, mixed>|null
     */
    public function formSchema(string $entity): ?array
    {
        return $this->compositions->optional($entity, 'form');
    }
}
