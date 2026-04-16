<?php

declare(strict_types=1);

namespace Flatpack\Lists;

/** @deprecated Use Flatpack\Lists\BulkActions instead. */
final class ListBulkActions
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return list<array{id: string, label: string, action?: string, icon?: string, variant: string}>
     */
    public static function fromSchema(?array $schema): array
    {
        return BulkActions::fromSchema($schema);
    }
}
