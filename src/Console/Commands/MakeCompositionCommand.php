<?php

declare(strict_types=1);

namespace Flatpack\Console\Commands;

use Closure;
use Flatpack\Composition\CompositionPathGuard;
use Flatpack\Services\Commands\BuildCompositionReplacementsService;
use Flatpack\Services\Commands\DiscoverFlatpackIconNamesService;
use Flatpack\Services\Commands\DiscoverModelsService;
use Flatpack\Services\Commands\MakeCompositionInput;
use Flatpack\Services\Commands\MakeCompositionToggleSet;
use Flatpack\Services\Commands\ModelSoftDeleteInspector;
use Flatpack\Services\Commands\ResolveNextNavOrderService;
use Flatpack\Services\Commands\WriteCompositionFilesService;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\number;
use function Laravel\Prompts\search;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

/**
 * Generates Flatpack YAML compositions using the same extension points as
 * {@see GeneratorCommand}: constructor-injected filesystem,
 * {@see GeneratorCommand::qualifyModel()},
 * {@see GeneratorCommand::isReservedName()},
 * {@see GeneratorCommand::makeDirectory()}, and {@see getStub()} for the primary stub path.
 */
#[AsCommand(name: 'flatpack:make')]
final class MakeCompositionCommand extends GeneratorCommand
{
    private const array TOGGLE_OPTION_FLAGS = [
        'basicActions' => ['with-basic-actions', 'without-basic-actions'],
        'bulkDelete' => ['with-bulk-delete', 'without-bulk-delete'],
        'softDeleteActions' => ['with-soft-delete-actions', 'without-soft-delete-actions'],
        'autoFields' => ['with-auto-fields', 'without-auto-fields'],
        'autoColumns' => ['with-auto-columns', 'without-auto-columns'],
    ];

    protected $name = 'flatpack:make';

    /**
     * @var string
     */
    protected $description = 'Create form.yaml and list.yaml Flatpack compositions for a model';

    /**
     * @var string
     */
    protected $type = 'Composition';

    /**
     * @var string
     */
    protected $signature = 'flatpack:make
                            {--model= : The model name (e.g. App\\Models\\Post)}
                            {--entity= : Singular entity slug (e.g. post)}
                            {--entities= : Plural entity slug (e.g. posts)}
                            {--menu= : Menu placement (main, secondary, bottom)}
                            {--icon= : Menu icon name}
                            {--nav-order= : Menu navigation order}
                            {--with-basic-actions : Include create/save/delete actions}
                            {--without-basic-actions : Exclude create/save/delete actions}
                            {--with-bulk-delete : Include delete bulk action}
                            {--without-bulk-delete : Exclude delete bulk action}
                            {--with-auto-fields : Auto-generate form fields from model}
                            {--without-auto-fields : Skip auto-generated form fields}
                            {--with-auto-columns : Auto-generate list columns from model}
                            {--without-auto-columns : Skip auto-generated list columns}
                            {--with-soft-delete-actions : Include restore/force_delete actions when model uses soft deletes}
                            {--without-soft-delete-actions : Exclude restore/force_delete actions}
                            {--force : Overwrite existing composition files if they already exist}';

    public function __construct(
        Filesystem $files,
        private readonly DiscoverModelsService $discoverModels,
        private readonly DiscoverFlatpackIconNamesService $discoverIcons,
        private readonly BuildCompositionReplacementsService $buildReplacements,
        private readonly WriteCompositionFilesService $writeFiles,
        private readonly ResolveNextNavOrderService $resolveNextNavOrder,
        private readonly ModelSoftDeleteInspector $softDeleteInspector,
    ) {
        parent::__construct($files);
    }

    /**
     * Writes form.yaml and list.yaml; failures use {@see \Illuminate\Console\Command::fail()} so the process exit code is non-zero
     * ({@see GeneratorCommand::handle()} returns false on error, which casts to exit code 0).
     */
    #[Override]
    public function handle()
    {
        try {
            $input = $this->resolveInput();
            $replacements = $this->buildReplacements->build($input);
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage());
        }

        try {
            $formStub = $this->replaceCompositionStub($this->compositionStubPath('form.yaml.stub'), $replacements);
            $listStub = $this->replaceCompositionStub($this->compositionStubPath('list.yaml.stub'), $replacements);
        } catch (FileNotFoundException $e) {
            $this->fail($e->getMessage());
        }

        try {
            $paths = $this->writeFiles->write($input, $formStub, $listStub);
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage());
        }

        $this->components->info(sprintf('%s [%s] created successfully.', $this->type, $paths['formPath']));
        $this->components->info(sprintf('%s [%s] created successfully.', $this->type, $paths['listPath']));

        return null;
    }

    public function isInteractiveMode(): bool
    {
        return $this->input->isInteractive();
    }

    #[Override]
    protected function getStub(): string
    {
        return $this->compositionStubPath('form.yaml.stub');
    }

    #[Override]
    protected function getArguments(): array
    {
        return [];
    }

    #[Override]
    protected function promptForMissingArgumentsUsing(): array
    {
        return [];
    }

    private function compositionStubPath(string $file): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function replaceCompositionStub(string $path, array $replacements): string
    {
        $stub = $this->files->get($path);

        foreach ($replacements as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }

        return $stub;
    }

    private function resolveInput(): MakeCompositionInput
    {
        $modelClass = $this->resolveModelClass();
        $basename = class_basename($modelClass);
        $shouldAskFollowUpQuestions = ! $this->hasModelOption() && $this->isInteractiveMode();
        $defaults = $this->defaultInputPreset(
            modelClass: $modelClass,
            basename: $basename
        );

        $selected = $defaults;
        if (! ($shouldAskFollowUpQuestions && $this->confirmSuggestedDefaults($defaults))) {
            $selected = $this->resolveInputPreset($defaults, $shouldAskFollowUpQuestions);
        }

        if (! CompositionPathGuard::isSafeSegment($selected['entities'])) {
            throw new InvalidArgumentException('The derived entity directory "' . $selected['entities'] . '" is not a safe path segment (use letters, numbers, underscores, or hyphens only).');
        }

        $toggles = $this->resolveToggles(
            defaults: $defaults['toggles'],
            askInteractively: $shouldAskFollowUpQuestions,
            usesSoftDeletes: $this->softDeleteInspector->usesSoftDeletes($modelClass),
        );

        return new MakeCompositionInput(
            modelClass: $modelClass,
            modelBasename: $basename,
            entity: $selected['entity'],
            entities: $selected['entities'],
            menu: $selected['menu'],
            icon: $selected['icon'],
            navOrder: $selected['navOrder'],
            includeBasicActions: $toggles->basicActions,
            includeBulkDelete: $toggles->bulkDelete,
            includeAutoFields: $toggles->autoFields,
            includeAutoColumns: $toggles->autoColumns,
            includeSoftDeleteActions: $toggles->softDeleteActions,
            force: (bool) $this->option('force'),
        );
    }

    /**
     * @return array{
     *   basename: string,
     *   modelClass: string,
     *   entity: string,
     *   entities: string,
     *   menu: string,
     *   icon: string,
     *   navOrder: int,
     *   toggles: MakeCompositionToggleSet
     * }
     */
    private function defaultInputPreset(string $modelClass, string $basename): array
    {
        $entity = $this->normalizeEntitySlug($basename);
        $menu = 'main';
        $toggles = MakeCompositionToggleSet::defaults();

        return [
            'basename' => $basename,
            'modelClass' => $modelClass,
            'entity' => $entity,
            'entities' => Str::plural($entity),
            'menu' => $menu,
            'icon' => 'folder-open',
            'navOrder' => $this->resolveNextNavOrder->resolve($menu),
            'toggles' => $toggles,
        ];
    }

    /**
     * @param  array{
     *   entity: string,
     *   entities: string,
     *   menu: string,
     *   icon: string,
     *   navOrder: int
     * }  $defaults
     * @return array{
     *   entity: string,
     *   entities: string,
     *   menu: string,
     *   icon: string,
     *   navOrder: int
     * }
     */
    private function resolveInputPreset(array $defaults, bool $askInteractively): array
    {
        $menu = $this->resolveMenu($defaults['menu'], $askInteractively);

        return [
            'entity' => $this->resolveEntity($defaults['entity'], $askInteractively),
            'entities' => $this->resolveEntities($defaults['entities'], $askInteractively),
            'menu' => $menu,
            'icon' => $this->resolveIcon($askInteractively),
            'navOrder' => $this->resolveNavOrder($menu, $askInteractively),
        ];
    }

    /**
     * @param  array{
     *   basename: string,
     *   modelClass: string,
     *   entity: string,
     *   entities: string,
     *   menu: string,
     *   icon: string,
     *   navOrder: int,
     *   toggles: MakeCompositionToggleSet
     * }  $defaults
     */
    private function confirmSuggestedDefaults(array $defaults): bool
    {
        if (! $this->isInteractiveMode()) {
            return false;
        }

        table(
            headers: ['Option', 'Value'],
            rows: [
                ['Model', '<fg=green>' . $defaults['modelClass'] . '</>'],
                ['Entity name (singular, plural)', $defaults['entity'] . ', ' . $defaults['entities']],
                ['Menu placement', $defaults['menu']],
                ['Menu icon', $defaults['icon']],
                ['Menu navigation order', (string) $defaults['navOrder']],
            ],
        );

        return confirm(
            label: 'Use suggested defaults?',
            default: true,
        );
    }

    private function resolveEntity(string $default, bool $askInteractively): string
    {
        $value = trim((string) $this->option('entity'));
        if ($value !== '') {
            return $this->normalizeEntitySlug($value);
        }

        if (! $this->shouldPrompt($askInteractively)) {
            return $default;
        }

        return $this->normalizeEntitySlug(text(
            label: 'Entity name (singular)',
            default: $default,
            required: true,
        ));
    }

    private function resolveEntities(string $default, bool $askInteractively): string
    {
        $value = trim((string) $this->option('entities'));
        if ($value !== '') {
            return $this->normalizeEntitySlug($value);
        }

        if (! $this->shouldPrompt($askInteractively)) {
            return $default;
        }

        return $this->normalizeEntitySlug(text(
            label: 'Entity name (plural)',
            default: $default,
            required: true,
        ));
    }

    private function resolveIcon(bool $askInteractively): string
    {
        $explicit = trim((string) $this->option('icon'));
        if ($explicit !== '') {
            return $explicit;
        }

        $icons = $this->discoverIcons->discover();
        if (! $this->shouldPrompt($askInteractively)) {
            return 'folder-open';
        }

        /** @var string $selected */
        $selected = search(
            label: 'Select icon',
            options: $this->searchableOptionsResolver($icons),
            placeholder: 'Search icon...',
        );

        return $selected;
    }

    private function resolveMenu(string $default, bool $askInteractively): string
    {
        $explicit = trim((string) $this->option('menu'));
        if ($explicit !== '') {
            return $this->normalizeMenu($explicit);
        }

        if (! $this->shouldPrompt($askInteractively)) {
            return $default;
        }

        /** @var string $selected */
        $selected = search(
            label: 'Select menu placement',
            options: $this->searchableOptionsResolver(['main', 'secondary', 'bottom']),
            placeholder: 'Search menu...',
        );

        return $this->normalizeMenu($selected);
    }

    private function resolveNavOrder(string $menu, bool $askInteractively): int
    {
        $raw = $this->option('nav-order');
        if (is_numeric($raw)) {
            return (int) $raw;
        }

        $default = $this->resolveNextNavOrder->resolve($menu);
        if (! $this->shouldPrompt($askInteractively)) {
            return $default;
        }

        $value = number(
            label: 'Menu navigation order',
            default: (string) $default,
            required: true,
            min: 0,
        );

        return (int) $value;
    }

    private function resolveToggles(
        MakeCompositionToggleSet $defaults,
        bool $askInteractively,
        bool $usesSoftDeletes,
    ): MakeCompositionToggleSet {
        if (! $this->shouldPrompt($askInteractively)) {
            return $this->resolveOptionToggles($defaults, $usesSoftDeletes);
        }

        $options = [
            'basic_actions' => 'Include basic actions: Create, Save, Delete',
            'bulk_delete' => 'Include basic bulk actions: Delete',
            'soft_delete_actions' => 'Include Restore and Force Delete actions',
            'auto_fields' => 'Auto-generate form fields',
            'auto_columns' => 'Auto-generate list columns',
        ];

        if (! $usesSoftDeletes) {
            unset($options['soft_delete_actions']);
        }

        /** @var array<int, string> $selected */
        $selected = multiselect(
            label: 'Check other options',
            options: $options,
            default: $this->toggleDefaultSelectionKeys($defaults),
        );

        return (new MakeCompositionToggleSet(
            basicActions: in_array('basic_actions', $selected, true),
            bulkDelete: in_array('bulk_delete', $selected, true),
            softDeleteActions: in_array('soft_delete_actions', $selected, true),
            autoFields: in_array('auto_fields', $selected, true),
            autoColumns: in_array('auto_columns', $selected, true),
        ))->withSoftDeleteGuard($usesSoftDeletes);
    }

    private function resolveOptionToggles(MakeCompositionToggleSet $defaults, bool $usesSoftDeletes): MakeCompositionToggleSet
    {
        $resolved = $defaults;

        foreach (self::TOGGLE_OPTION_FLAGS as $key => [$with, $without]) {
            if ((bool) $this->option($with)) {
                $resolved = $this->toggleWith($resolved, $key, true);
            }
            if ((bool) $this->option($without)) {
                $resolved = $this->toggleWith($resolved, $key, false);
            }
        }

        return $resolved->withSoftDeleteGuard($usesSoftDeletes);
    }

    private function hasModelOption(): bool
    {
        return trim((string) $this->option('model')) !== '';
    }

    /**
     * @return class-string<Model>
     */
    private function resolveModelClass(): string
    {
        $hasModelOption = $this->hasModelOption();
        $rawModel = trim((string) $this->option('model'));
        if (! $hasModelOption && ! $this->isInteractiveMode()) {
            throw new InvalidArgumentException('The [--model] option is required in non-interactive mode.');
        }

        if (! $hasModelOption) {
            $models = $this->discoverModels->discover();

            if ($models === []) {
                throw new InvalidArgumentException('No Eloquent models were found in the host app.');
            }

            /** @var class-string<Model> $selected */
            $selected = search(
                label: 'Select model',
                options: $this->searchableOptionsResolver($models),
                placeholder: 'Search model class...',
            );

            $rawModel = $selected;
        }

        $modelClass = $this->normalizeModelClass($rawModel);
        if ($modelClass === '') {
            throw new InvalidArgumentException('A model name is required.');
        }

        if (! class_exists($modelClass)) {
            throw new InvalidArgumentException('Model class not found: ' . $modelClass);
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException('The [--model] class must extend ' . Model::class . ': ' . $modelClass);
        }

        if ($this->isReservedName(class_basename($modelClass))) {
            throw new InvalidArgumentException('The name "' . class_basename($modelClass) . '" is reserved by PHP.');
        }

        return $modelClass;
    }

    private function normalizeModelClass(string $model): string
    {
        if ($model === '') {
            return '';
        }

        $normalized = ltrim(str_replace('/', '\\', $model), '\\');
        if (str_contains($normalized, '\\')) {
            return $normalized;
        }

        $appNamespace = rtrim($this->laravel->getNamespace(), '\\');
        $collapsedAppModelsPrefix = str_replace('\\', '', $appNamespace) . 'Models';
        if (str_starts_with($normalized, $collapsedAppModelsPrefix)) {
            $tail = mb_substr($normalized, mb_strlen($collapsedAppModelsPrefix));
            if ($tail !== '') {
                return $appNamespace . '\\Models\\' . $tail;
            }
        }

        $candidate = $appNamespace . '\\Models\\' . $normalized;

        if (class_exists($candidate)) {
            return $candidate;
        }

        return $appNamespace . '\\' . $normalized;
    }

    private function shouldPrompt(bool $askInteractively): bool
    {
        return $this->isInteractiveMode() && $askInteractively;
    }

    private function normalizeEntitySlug(string $value): string
    {
        return Str::lower(Str::snake($value));
    }

    private function normalizeMenu(string $value): string
    {
        $normalized = Str::lower(trim($value));
        if (in_array($normalized, ['main', 'secondary', 'bottom'], true)) {
            return $normalized;
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid [--menu] value "%s". Allowed: main, secondary, bottom.',
            $value
        ));
    }

    /**
     * @param  list<string>  $items
     * @return Closure(string): array<string, string>
     */
    private function searchableOptionsResolver(array $items): Closure
    {
        return function (string $value) use ($items): array {
            $needle = Str::lower(trim($value));
            $filtered = array_values(array_filter(
                $items,
                fn (string $item): bool => $needle === '' || str_contains(Str::lower($item), $needle),
            ));

            /** @var array<string, string> */
            return array_combine($filtered, $filtered) ?: [];
        };
    }

    /**
     * @return list<string>
     */
    private function toggleDefaultSelectionKeys(MakeCompositionToggleSet $defaults): array
    {
        return array_keys(array_filter([
            'basic_actions' => $defaults->basicActions,
            'bulk_delete' => $defaults->bulkDelete,
            'soft_delete_actions' => $defaults->softDeleteActions,
            'auto_fields' => $defaults->autoFields,
            'auto_columns' => $defaults->autoColumns,
        ]));
    }

    private function toggleWith(MakeCompositionToggleSet $resolved, string $key, bool $value): MakeCompositionToggleSet
    {
        return match ($key) {
            'basicActions' => $resolved->withBasicActions($value),
            'bulkDelete' => $resolved->withBulkDelete($value),
            'softDeleteActions' => $resolved->withSoftDeleteActions($value),
            'autoFields' => $resolved->withAutoFields($value),
            'autoColumns' => $resolved->withAutoColumns($value),
            default => $resolved,
        };
    }
}
