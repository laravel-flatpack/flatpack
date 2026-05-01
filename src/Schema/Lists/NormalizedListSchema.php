<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

use ArrayAccess;
use LogicException;

/**
 * Output of {@see ListSchemaNormalizer}; safe to expose on Inertia props.
 */
final readonly class NormalizedListSchema implements ArrayAccess
{
    /**
     * @param  array<string, mixed>  $schema
     */
    public function __construct(private array $schema) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->schema;
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && array_key_exists($offset, $this->schema);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) ? ($this->schema[$offset] ?? null) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('NormalizedListSchema is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('NormalizedListSchema is immutable.');
    }
}
