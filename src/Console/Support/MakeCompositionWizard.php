<?php

declare(strict_types=1);

namespace Flatpack\Console\Support;

use Flatpack\Composition\CompositionPathGuard;
use InvalidArgumentException;

final readonly class MakeCompositionWizard
{
    public function __construct(
        private ModelSoftDeleteInspector $softDeleteInspector,
    ) {}

    /**
     * @param  callable(string, string): array{
     *   basename: string,
     *   modelClass: string,
     *   entity: string,
     *   entities: string,
     *   menu: string,
     *   icon: string,
     *   navOrder: int,
     *   toggles: MakeCompositionToggleSet
     * }  $defaultInputPreset
     * @param  callable(array{
     *   basename: string,
     *   modelClass: string,
     *   entity: string,
     *   entities: string,
     *   menu: string,
     *   icon: string,
     *   navOrder: int,
     *   toggles: MakeCompositionToggleSet
     * }): bool  $confirmSuggestedDefaults
     * @param  callable(array{
     *   entity: string,
     *   entities: string,
     *   menu: string,
     *   icon: string,
     *   navOrder: int
     * }, bool): array{
     *   entity: string,
     *   entities: string,
     *   menu: string,
     *   icon: string,
     *   navOrder: int
     * }  $resolveInputPreset
     * @param  callable(MakeCompositionToggleSet, bool, bool): MakeCompositionToggleSet  $resolveToggles
     */
    public function resolve(
        string $modelClass,
        string $modelBasename,
        bool $hasModelOption,
        bool $interactiveMode,
        bool $force,
        callable $defaultInputPreset,
        callable $confirmSuggestedDefaults,
        callable $resolveInputPreset,
        callable $resolveToggles,
    ): MakeCompositionInput {
        $shouldAskFollowUpQuestions = ! $hasModelOption && $interactiveMode;
        $defaults = $defaultInputPreset($modelClass, $modelBasename);

        $selected = $defaults;
        if (! ($shouldAskFollowUpQuestions && $confirmSuggestedDefaults($defaults))) {
            $selected = $resolveInputPreset($defaults, $shouldAskFollowUpQuestions);
        }

        if (! CompositionPathGuard::isSafeSegment($selected['entities'])) {
            throw new InvalidArgumentException('The derived entity directory "' . $selected['entities'] . '" is not a safe path segment (use letters, numbers, underscores, or hyphens only).');
        }

        $toggles = $resolveToggles(
            $defaults['toggles'],
            $shouldAskFollowUpQuestions,
            $this->softDeleteInspector->usesSoftDeletes($modelClass),
        );

        return new MakeCompositionInput(
            modelClass: $modelClass,
            modelBasename: $modelBasename,
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
            force: $force,
        );
    }
}
