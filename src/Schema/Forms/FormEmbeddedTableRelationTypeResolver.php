<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms;

use Flatpack\Support\CompositionDebugLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

/**
 * Fills `table_relation_type` on `type: table` fields with a `relation` key, using
 * the parent form model class. Uses {@see Relation} {@code instanceof} (when a model
 * is available) and {@see ReflectionMethod} return types as a fallback.
 *
 * @phpstan-type TableRelationType 'belongs_to_many'|'has_many'|'morph_many'|'morph_to_many'|'has_one'|'unknown'
 */
final class FormEmbeddedTableRelationTypeResolver
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    public function enrichFormSchema(
        ?array $schema,
        string $formModelClass,
        ?Model $formModel = null,
        ?CompositionDebugLog $log = null,
    ): ?array {
        if ($schema === null) {
            return null;
        }

        $formModelClass = trim($formModelClass);
        if ($formModelClass === '' || ! class_exists($formModelClass) || ! is_subclass_of($formModelClass, Model::class)) {
            return $schema;
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return $schema;
        }

        foreach ($fields as $id => $field) {
            if (! is_array($field)) {
                continue;
            }

            $type = $field['type'] ?? null;
            if (trim((string) $type) !== 'table') {
                continue;
            }

            $relation = isset($field['relation']) ? trim((string) $field['relation']) : '';
            if ($relation === '') {
                continue;
            }

            if ($this->hasAuthoritativeType($field)) {
                continue;
            }

            $kind = $this->resolveKind(
                $formModelClass,
                $relation,
                $formModel,
            );

            if ($kind === null) {
                $log?->add(sprintf(
                    'Form field table "%s": could not resolve table_relation_type for relation "%s" on %s (set it explicitly in YAML to silence).',
                    (string) ($field['id'] ?? $id),
                    $relation,
                    $formModelClass,
                ));
                $field['table_relation_type'] = 'unknown';
            } else {
                $field['table_relation_type'] = $kind;
            }

            $fields[$id] = $field;
        }

        $schema['fields'] = $fields;

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function hasAuthoritativeType(array $field): bool
    {
        if (! array_key_exists('table_relation_type', $field)) {
            return false;
        }

        $k = $field['table_relation_type'];

        return is_string($k) && trim($k) !== '';
    }

    private function resolveKind(
        string $formModelClass,
        string $relation,
        ?Model $formModel,
    ): ?string {
        if ($formModel !== null) {
            $k = $this->resolveByRelationInstance($formModel, $relation);
            if ($k !== null) {
                return $k;
            }
        }

        $k = $this->resolveByNewModelInstance($formModelClass, $relation);
        if ($k !== null) {
            return $k;
        }

        $k = $this->resolveByReflection($formModelClass, $relation);
        if ($k !== null) {
            return $k;
        }

        return null;
    }

    private function resolveByRelationInstance(Model $model, string $relation): ?string
    {
        if (! method_exists($model, $relation)) {
            return null;
        }

        try {
            $rel = $model->{$relation}();
        } catch (Throwable) {
            return null;
        }

        return $this->mapRelationObjectToKind($rel);
    }

    private function resolveByNewModelInstance(string $formModelClass, string $relation): ?string
    {
        if (! method_exists($formModelClass, $relation)) {
            return null;
        }

        try {
            $model = new $formModelClass;
            if (! $model instanceof Model) {
                return null;
            }
            $rel = $model->{$relation}();
        } catch (Throwable) {
            return null;
        }

        return $this->mapRelationObjectToKind($rel);
    }

    private function mapRelationObjectToKind(mixed $rel): ?string
    {
        if (! $rel instanceof Relation) {
            return null;
        }

        return match (true) {
            $rel instanceof MorphToMany => 'morph_to_many',
            $rel instanceof BelongsToMany => 'belongs_to_many',
            $rel instanceof MorphMany => 'morph_many',
            $rel instanceof HasMany => 'has_many',
            $rel instanceof HasOne => 'has_one',
            default => null,
        };
    }

    private function resolveByReflection(string $formModelClass, string $relation): ?string
    {
        if (! method_exists($formModelClass, $relation)) {
            return null;
        }

        try {
            $method = new ReflectionMethod($formModelClass, $relation);
        } catch (Throwable) {
            return null;
        }

        $type = $method->getReturnType();

        return $this->mapReflectionReturnTypeToKind($type);
    }

    private function mapReflectionReturnTypeToKind(?ReflectionType $type): ?string
    {
        if ($type === null) {
            return null;
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $inner) {
                if ($inner instanceof ReflectionNamedType) {
                    $k = $this->mapTypeNameStringToKind($inner->getName());
                    if ($k !== null) {
                        return $k;
                    }
                }
            }

            return null;
        }

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return $this->mapTypeNameStringToKind($type->getName());
    }

    private function mapTypeNameStringToKind(string $name): ?string
    {
        $name = ltrim($name, '\\');
        if (! class_exists($name)) {
            return null;
        }
        if (is_a($name, MorphToMany::class, true)) {
            return 'morph_to_many';
        }
        if (is_a($name, BelongsToMany::class, true)) {
            return 'belongs_to_many';
        }
        if (is_a($name, MorphMany::class, true)) {
            return 'morph_many';
        }
        if (is_a($name, HasMany::class, true)) {
            return 'has_many';
        }
        if (is_a($name, HasOne::class, true)) {
            return 'has_one';
        }

        return null;
    }
}
