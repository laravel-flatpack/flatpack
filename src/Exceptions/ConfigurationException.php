<?php

namespace Faustoq\Flatpack\Exceptions;

use Exception;
use Facade\IgnitionContracts\BaseSolution;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;

class ConfigurationException extends Exception implements ProvidesSolution
{
    /** @return  \Facade\IgnitionContracts\Solution */
    public function getSolution(): Solution
    {
        return BaseSolution::create('Flatpack configuration is missing.')
            ->setSolutionDescription('Generate your first Flatpack template using `php artisan make:flatpack` command.')
            ->setDocumentationLinks([
                'Package documentation' => 'https://github.com/faustoq/laravel-flatpack',
            ]);
    }
}
