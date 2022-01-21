<?php

namespace Faustoq\Flatpack\Commands;

use Illuminate\Console\Command;

class FlatpackCommand extends Command
{
    public $signature = 'make:flatpack';

    public $description = 'Generate Flatpack template files';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
