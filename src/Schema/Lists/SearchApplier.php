<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

use Illuminate\Database\Eloquent\Builder;

final class SearchApplier
{
    /**
     * @param  list<SearchDefinition>  $searchableDefs
     */
    public static function apply(Builder $query, array $searchableDefs, string $searchTerm): void
    {
        if ($searchableDefs === []) {
            return;
        }

        $query->where(function (Builder $nested) use ($searchableDefs, $searchTerm): void {
            $hasCondition = false;
            foreach ($searchableDefs as $def) {
                if ($def->kind === 'relation') {
                    $relation = (string) $def->relation;
                    $relationName = (string) $def->relationName;
                    if ($relation === '' || $relationName === '') {
                        continue;
                    }
                    if ($hasCondition) {
                        $nested->orWhereHas($relation, function (Builder $relationQuery) use ($relationName, $searchTerm): void {
                            $relationQuery->where($relationName, 'like', '%' . $searchTerm . '%');
                        });
                    } else {
                        $nested->whereHas($relation, function (Builder $relationQuery) use ($relationName, $searchTerm): void {
                            $relationQuery->where($relationName, 'like', '%' . $searchTerm . '%');
                        });
                    }
                    $hasCondition = true;

                    continue;
                }

                $column = (string) $def->id;
                if ($column === '') {
                    continue;
                }
                if (! $hasCondition) {
                    $nested->where($column, 'like', '%' . $searchTerm . '%');
                } else {
                    $nested->orWhere($column, 'like', '%' . $searchTerm . '%');
                }
                $hasCondition = true;
            }
        });
    }
}
