<?php

namespace Flatpack\Commands;

use Flatpack\Facades\Flatpack;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\{render};

#[AsCommand(name: 'make:flatpack')]
class MakeCommand extends GeneratorCommand
{
    use GeneratesFormFields;
    use GeneratesListColumns;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:flatpack';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'make:flatpack';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Flatpack template composition files';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Flatpack templates';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->printView('flatpack::cli.title');

        $name = $this->getNameInput();

        $path = $this->getPath($name);

        if ($this->isReservedName($name)) {
            $this->components->error('The name "'.$name.'" is reserved by PHP.');

            return false;
        }

        if (! class_exists($this->getModelClass())) {
            $this->components->error('The model "'.$this->getModelClass().'" does not exist.');

            return false;
        }

        if ((! $this->hasOption('force') || ! $this->option('force')) &&
            ($this->alreadyExists($path . '/form.yaml') || $this->alreadyExists($path . '/list.yaml'))) {
            $this->components->error($this->type.' already exist in /'.$path.' path.');

            return false;
        }

        $this->makeDirectory(str_replace($this->resolveStubPath('/stubs'), $path, $this->getStub()));

        $this->createFormTemplate();

        $this->createListTemplate();

        $this->printView('flatpack::cli.results', [
            'model' => $this->getModelClass(),
            'files' => collect([
                'Form Template' => $this->getRelativePath($name) . '/form.yaml',
                'List Template' => $this->getRelativePath($name) . '/list.yaml',
            ]),
        ]);

        return false;
    }

    /**
     * Prints the command title.
     *
     * @param string $view
     * @param array $data
     * @return void
     */
    protected function printView($view, $data = [])
    {
        render((string) view($view, $data));
    }

    /**
     * Create a form template.
     *
     * @return void
     */
    protected function createFormTemplate()
    {
        $file = $this->getPath($this->getNameInput()).'/form.yaml';

        $this->files->put($file, $this->buildTemplate('form'));
    }

    /**
     * Create a list template.
     *
     * @return void
     */
    protected function createListTemplate()
    {
        $file = $this->getPath($this->getNameInput()).'/list.yaml';

        $this->files->put($file, $this->buildTemplate('list'));
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildTemplate($template)
    {
        $stub = $this->files->get($this->resolveStubPath("/stubs/{$template}.yaml"));

        return $this->replaceTokens($stub, $template);
    }

    /**
     * Replace the tokens for the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceTokens($stub, $template)
    {
        $replace = collect([
                '{{ namespacedModel }}' => $this->getModelClass(),
                '{{ model }}' => $this->getNameInput(),
                '{{ entity }}' => $this->getEntityName(),
                '{{ icon }}' => $this->getIconOption(),
            ])
            ->merge($this->getTemplateBlocks($template))
            ->toArray();

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $stub
        );
    }

    protected function getTemplateBlocks($template)
    {
        $model = $this->getModelClass();

        if ($template === 'form') {
            return [
                '{{ header }}' => $this->generateFormHeader($model),
                '{{ fields }}' => $this->generateFormFields($model),
                '{{ sidebar }}' => $this->generateFormSidebar($model),
            ];
        }

        return [
            '{{ columns }}' => $this->GeneratesListColumns($model),
        ];
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        return Str::finish($this->laravel->basePath(), "/") .
            $this->getRelativePath($name);
    }

    protected function getRelativePath($name)
    {
        return Str::finish(config('flatpack.directory', 'flatpack'), "/") .
            Flatpack::entityName($name);
    }

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
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/'))) ? $customPath : __DIR__.$stub;
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $name
     * @return bool
     */
    protected function alreadyExists($name)
    {
        return $this->files->exists($name);
    }

    /**
     * Get the name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return Str::studly(trim($this->argument('name')));
    }

    /**
     * Get the name from the input.
     *
     * @return string
     */
    protected function getIconOption()
    {
        return $this->option('icon') ?? 'folder';
    }

    /**
     * Get the name from the input.
     *
     * @return string
     */
    protected function getEntityName()
    {
        return Flatpack::entityName($this->getNameInput());
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getModelClass()
    {
        return Str::finish(config('flatpack.models'), '\\') . $this->getNameInput();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['icon', null, InputOption::VALUE_OPTIONAL, 'Specify an icon name'],
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files'],
        ];
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        $option = collect($this->components->choice('Select an icon', [
            'none',
            'folder',
            'collection',
            'book-open',
            'tag',
            'database',
            'chat',
            'inbox',
            'newspaper',
            'shopping-cart',
            'other',
        ], default: 'folder', multiple: false))
        ->reject('none')
        ->map(fn ($option) => match ($option) {
            'other' => $this->components->ask('Icon name:', 'folder'),
            default => $option,
        })->first();

        $input->setOption('icon', $option);
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'name' => 'What is the model name?',
        ];
    }
}
