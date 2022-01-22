<?php

namespace Faustoq\Flatpack\Commands;

use Illuminate\Console\Command;

class MakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:flatpack {name} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Flatpack template composition files';

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
     * Handle the command.
     */
    public function handle()
    {
        $this->line("\n");

        if ($this->option('force')) {
            $this->warn("Force option enabled, will overwrite existing files\n");
        }

        $this->call('flatpack:make:list', [
            'name' => $this->getNameInput(),
            '--force' => $this->option('force'),
        ]);

        $this->call('flatpack:make:form', [
            'name' => $this->getNameInput(),
            '--force' => $this->option('force'),
        ]);

        $this->line("\nDone!\n");
    }
}
