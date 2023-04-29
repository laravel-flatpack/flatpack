<?php

namespace Flatpack\Commands;

use Flatpack\Commands\Traits\CreatesDirectory;
use Flatpack\Commands\Traits\WithBuildClass;
use Flatpack\Commands\Traits\WithStub;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeListCommand extends GeneratorCommand
{
    use WithStub;
    use WithBuildClass;
    use CreatesDirectory;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'flatpack:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Flatpack list template composition files';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Flatpack list.yaml file';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/list.yaml');
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        return $this->flatpackDir($name) . "/list.yaml";
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return trim($this->argument('name'));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the model'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_OPTIONAL, 'Overwrite existing files'],
        ];
    }
}
