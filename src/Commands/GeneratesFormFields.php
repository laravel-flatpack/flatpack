<?php

namespace Flatpack\Commands;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

trait GeneratesFormFields
{
    protected function generateFormHeader($modelClass)
    {
        $model = new $modelClass();

        $primary = $model->getKeyName();

        $results = [
            $primary => [
                'type' => 'heading',
                'label' => Str::of($primary)->studly()->toString(),
                'size' => 'large',
            ],
        ];

        return count($results) ? Yaml::dump(['header' => $results]) : '';
    }

    protected function generateFormFields($modelClass)
    {
        $model = new $modelClass();

        $table = $model->getTable();

        $primary = $model->getKeyName();

        $fillable = collect($model->getFillable())
            ->except($primary)
            ->toArray();

        $results = [];

        foreach ($fillable as $field) {
            if (Schema::hasColumn($table, $field)) {
                $type = Schema::getColumnType($table, $field);
                $label = Str::of($field)
                    ->replace('-', ' ')
                    ->replace('_', ' ')
                    ->headline()
                    ->toString();

                $fieldTypes = [
                    'string' => [
                        'type' => 'text',
                        'label' => $label,
                        'placeholder' => $label,
                    ],
                    'text' => [
                        'type' => 'textarea',
                        'label' => $label,
                        'placeholder' => $label,
                    ],
                ];

                $results[$field] = $fieldTypes[$type] ?? $fieldTypes['string'];
            }
        }

        return count($results) ? Yaml::dump(['main' => $results]) : '';
    }

    protected function generateFormSidebar($modelClass)
    {
        $model = new $modelClass();

        $table = $model->getTable();

        $results = [];

        if (Schema::hasColumn($table, 'created_at')) {
            $results['created_at'] = [
                'type' => 'datetime-picker',
                'label' => __('Created'),
                'placeholder' => __('Created at'),
            ];
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $results['updated_at'] = [
                'type' => 'datetime-picker',
                'label' => __('Updated'),
                'placeholder' => __('Updated at'),
            ];
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $results['delete_action'] = [
                'type' => 'button',
                'label' => __('Move to trash'),
                'action' => 'delete',
                'style' => 'danger',
                'form' => 'edit',
                'confirm' => true,
            ];
        }

        return count($results) ? Yaml::dump(['sidebar' => $results]) : '';
    }
}
