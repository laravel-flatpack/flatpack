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
            // Escape LIKE wildcards using '!' as the escape character (cross-DB safe).
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $searchTerm);
            $pattern = '%' . $escaped . '%';
            $hasCondition = false;
            foreach ($searchableDefs as $def) {
                if ($def->kind === 'relation') {
                    $relation = (string) $def->relation;
                    $relationName = (string) $def->relationName;
                    if ($relation === '' || $relationName === '') {
                        continue;
                    }
                    if ($hasCondition) {
                        $nested->orWhereHas($relation, function (Builder $relationQuery) use ($relationName, $pattern): void {
                            $wrapped = $relationQuery->getQuery()->getGrammar()->wrap($relationName);
                            $relationQuery->whereRaw("{$wrapped} LIKE ? ESCAPE '!'", [$pattern]);
                        });
                    } else {
                        $nested->whereHas($relation, function (Builder $relationQuery) use ($relationName, $pattern): void {
                            $wrapped = $relationQuery->getQuery()->getGrammar()->wrap($relationName);
                            $relationQuery->whereRaw("{$wrapped} LIKE ? ESCAPE '!'", [$pattern]);
                        });
                    }
                    $hasCondition = true;

                    continue;
                }

                $column = (string) $def->id;
                if ($column === '') {
                    continue;
                }
                $wrapped = $nested->getQuery()->getGrammar()->wrap($column);
                if (! $hasCondition) {
                    $nested->whereRaw("{$wrapped} LIKE ? ESCAPE '!'", [$pattern]);
                } else {
                    $nested->orWhereRaw("{$wrapped} LIKE ? ESCAPE '!'", [$pattern]);
                }
                $hasCondition = true;
            }
        });
    }
}
