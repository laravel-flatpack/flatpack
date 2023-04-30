<?php

namespace Flatpack\Commands;

use Symfony\Component\Yaml\Yaml;

trait GeneratesListColumns
{
    protected function GeneratesListColumns($modelClass)
    {
        $results = [];

        return count($results) ? Yaml::dump($results) : '';
    }
}
