<?php

declare(strict_types=1);

namespace Flatpack\Console\Composition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Builds guessed `fields` / `columns` YAML fragments from an Eloquent model's
 * fillable attributes (or cast keys when fillable is empty) and casts.
 *
 * @phpstan-type ParsedAttributeRow array{
 *     attribute: string,
 *     rule: array{form_type: 'text'|'date-picker', column_date_kind: 'date'|'datetime'},
 *     label: string
 * }
 */
final class ModelCompositionDefaults
{
    /**
     * @return list<ParsedAttributeRow>
     */
    public static function findAttributes(Model $model): array
    {
        $parsed = [];

        foreach (self::candidateAttributes($model) as $attribute) {
            if (! self::shouldIncludeAttribute($model, $attribute)) {
                continue;
            }

            $cast = $model->getCasts()[$attribute] ?? null;
            $rule = self::classifyCast($cast);
            if ($rule === null) {
                continue;
            }

            $parsed[] = [
                'attribute' => $attribute,
                'rule' => $rule,
                'label' => self::fieldLabel($attribute),
            ];
        }

        return $parsed;
    }

    /**
     * @param  list<ParsedAttributeRow>  $parsed
     * @return array<string, array<string, mixed>>
     */
    public static function guessFormFields(array $parsed): array
    {
        $fields = [];

        foreach ($parsed as $row) {
            $attribute = $row['attribute'];
            $label = $row['label'];
            $rule = $row['rule'];

            if ($rule['form_type'] === 'text') {
                $fields[$attribute] = [
                    'type' => 'text',
                    'label' => $label,
                    'placeholder' => $label,
                ];

                continue;
            }

            $fields[$attribute] = [
                'type' => 'date-picker',
                'label' => $label,
                'placeholder' => $label,
            ];
        }

        return $fields;
    }

    /**
     * @param  list<ParsedAttributeRow>  $parsed
     * @return list<array<string, mixed>>
     */
    public static function guessTableColumns(array $parsed): array
    {
        $columns = [];

        foreach ($parsed as $row) {
            $attribute = $row['attribute'];
            $label = $row['label'];
            $rule = $row['rule'];

            if ($rule['form_type'] === 'text') {
                $columns[] = [
                    'id' => $attribute,
                    'label' => $label,
                    'type' => 'text',
                    'truncate' => 50,
                    'sortable' => true,
                    'searchable' => true,
                ];

                continue;
            }

            $columns[] = [
                'id' => $attribute,
                'label' => $label,
                'type' => $rule['column_date_kind'],
                'sortable' => true,
                'searchable' => false,
            ];
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    private static function candidateAttributes(Model $model): array
    {
        $fillable = $model->getFillable();
        if ($fillable !== []) {
            /** @var list<string> */
            return array_values(array_unique($fillable));
        }

        /** @var list<string> */
        return array_keys($model->getCasts());
    }

    private static function shouldIncludeAttribute(Model $model, string $attribute): bool
    {
        if ($attribute === '') {
            return false;
        }

        if ($attribute === 'password' || $attribute === 'remember_token') {
            return false;
        }

        return ! in_array($attribute, $model->getHidden(), true);
    }

    private static function fieldLabel(string $attribute): string
    {
        return Str::headline(Str::snake(str_replace('.', '_', $attribute)));
    }

    /**
     * @return array{form_type: 'text'|'date-picker', column_date_kind: 'date'|'datetime'}|null null = omit attribute
     */
    private static function classifyCast(mixed $cast): ?array
    {
        if ($cast === null || $cast === '') {
            return ['form_type' => 'text', 'column_date_kind' => 'datetime'];
        }

        if (! is_string($cast)) {
            return null;
        }

        if (str_contains($cast, '\\')) {
            return null;
        }

        $base = mb_strtolower(explode(':', trim($cast), 2)[0]);

        if (enum_exists($base)) {
            return null;
        }

        return match ($base) {
            'int', 'integer', 'real', 'float', 'double', 'decimal',
            'bool', 'boolean',
            'array', 'json', 'object', 'collection',
            'encrypted', 'hashed', 'password' => null,
            'date', 'immutable_date' => [
                'form_type' => 'date-picker',
                'column_date_kind' => 'date',
            ],
            'datetime', 'immutable_datetime', 'datetimeimmutable', 'timestamp' => [
                'form_type' => 'date-picker',
                'column_date_kind' => 'datetime',
            ],
            'string' => ['form_type' => 'text', 'column_date_kind' => 'datetime'],
            default => ['form_type' => 'text', 'column_date_kind' => 'datetime'],
        };
    }
}
