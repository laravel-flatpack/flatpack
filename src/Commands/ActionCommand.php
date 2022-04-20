<?php

namespace Flatpack\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ActionCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = "flatpack:action";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Generate Flatpack action class";

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = "Flatpack action class";

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath("/stubs/Action.stub");
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, "/")))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $actionName = $this->getNameInput();

        $replace = [
            "{{ actionName }}" => $actionName,
            "{{actionName}}" => $actionName,
        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return Str::studly(trim($this->argument("name")));
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');

        $name = str_replace('/', '\\', $name);

        $rootNamespace = $this->rootNamespace() . "Actions\\Flatpack";

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, "\\"))."\\".$name
        );
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_OPTIONAL, 'Overwrite existing files'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        $this->line("\n 📦 Flatpack \n");

        if ($this->hasOption('force') && $this->option('force') === true) {
            $this->warn("Force option enabled, will overwrite existing files\n");
        }

        parent::handle();
    }
}
