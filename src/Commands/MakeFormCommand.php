<?php

namespace Flatpack\Commands;

class MakeFormCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'flatpack:make:form';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Flatpack form template composition files';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Flatpack form.yaml file';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/form.yaml');
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        return $this->flatpackDir($name) . "/form.yaml";
    }
}
