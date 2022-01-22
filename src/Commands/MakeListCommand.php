<?php

namespace Faustoq\Flatpack\Commands;

class MakeListCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'flatpack:make:list';

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
}
