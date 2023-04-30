<?php

namespace Flatpack\Commands;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

trait GeneratesListColumns
{
    protected function generatesListColumns($modelClass)
    {
        $model = new $modelClass();

        $table = $model->getTable();

        $primary = $model->getKeyName();

        $results = [
            $primary => [
                'label' => Str::of($primary)->studly()->toString(),
                'sortable' => true,
                'invisible' => false,
            ],
        ];

        if (Schema::hasColumn($table, 'created_at')) {
            $results['created_at'] = [
                'type' => 'datetime',
                'label' => __('Created'),
                'format' => 'M d, Y  h:i a',
                'sortable' => true,
                'invisible' => false,
            ];
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $results['updated_at'] = [
                'type' => 'datetime',
                'label' => __('Updated'),
                'format' => 'M d, Y  h:i a',
                'sortable' => true,
                'invisible' => false,
            ];
        }

        return count($results) ? Yaml::dump(['columns' => $results]) : '';
    }

    protected function generatesListBulkActions($modelClass)
    {
        $model = new $modelClass();

        $table = $model->getTable();

        $results = [];

        if (Schema::hasColumn($table, 'deleted_at')) {
            $results['delete'] = [
                'label' => __('Delete'),
                'action' => 'delete',
                'confirm' => true,
            ];
        }

        return count($results) ? Yaml::dump(['bulk' => $results]) : '';
    }
}
