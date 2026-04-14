<?php

declare(strict_types=1);

namespace Flatpack\Lists;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class RelationSerializer
{
    public static function serializePayload(mixed $related, string $relationName, string $relationValue): mixed
    {
        if ($related === null) {
            return null;
        }

        if ($related instanceof Model) {
            return [
                $relationValue => $related->getAttribute($relationValue),
                $relationName => $related->getAttribute($relationName),
            ];
        }

        if ($related instanceof Collection) {
            return $related
                ->map(function (mixed $item) use ($relationName, $relationValue): ?array {
                    if (! $item instanceof Model) {
                        return null;
                    }

                    return [
                        $relationValue => $item->getAttribute($relationValue),
                        $relationName => $item->getAttribute($relationName),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        return null;
    }
}
