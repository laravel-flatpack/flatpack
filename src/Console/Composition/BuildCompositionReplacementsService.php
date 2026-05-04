<?php

declare(strict_types=1);

namespace Flatpack\Console\Composition;

use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

final class BuildCompositionReplacementsService
{
    /**
     * @return array<string, string>
     */
    public function build(MakeCompositionInput $input): array
    {
        $entityLabel = Str::headline($input->entity);
        $entitiesLabel = Str::headline($input->entities);

        $attributes = ModelCompositionDefaults::findAttributes(new $input->modelClass());
        $formPageView = $this->buildFormPageView($input, $attributes);
        $tablePageView = $this->buildTablePageView($input, $attributes);

        return [
            '{{ DummyModel }}' => $input->modelClass,
            '{{ DummyFormTitle }}' => $entityLabel,
            '{{ DummyListTitle }}' => $entitiesLabel,
            '{{ DummyIcon }}' => $input->icon,
            '{{ DummyMenu }}' => $input->menu,
            '{{ DummyEntity }}' => $entityLabel,
            '{{ DummyEntities }}' => $entitiesLabel,
            '{{ DummyNavOrder }}' => (string) $input->navOrder,
            '{{ DummyFormActionsDefinition }}' => $this->formActionsDefinition($input, $entityLabel),
            '{{ DummyListActionsDefinition }}' => $this->listActionsDefinition($input, $entityLabel),
            '{{ DummyBulkActionsDefinition }}' => $this->bulkActionsDefinition($input, $entitiesLabel),
            '{{ DummyFieldsDefinition }}' => Yaml::dump($formPageView, PHP_INT_MAX, 2),
            '{{ DummyColumnsDefinition }}' => Yaml::dump($tablePageView, PHP_INT_MAX, 2),
        ];
    }

    private function buildFormPageView(MakeCompositionInput $input, array $attributes): array
    {
        if (! $input->includeAutoFields) {
            return ['fields' => []];
        }

        return ['fields' => ModelCompositionDefaults::guessFormFields($attributes)];
    }

    private function buildTablePageView(MakeCompositionInput $input, array $attributes): array
    {
        if (! $input->includeAutoColumns) {
            return ['columns' => []];
        }

        if ($input->includeSoftDeleteActions) {
            return [
                'columns' => ModelCompositionDefaults::guessTableColumns($attributes),
                'tabs' => [
                    'records' => [
                        'label' => 'Records',
                        'icon' => 'table',
                    ],
                    'trashed' => [
                        'label' => 'Trashed',
                        'icon' => 'trash',
                        'scope' => 'onlyTrashed',
                        'row_click' => 'none',
                        'reorderable' => false,
                        'bulk_actions' => [
                            'restore' => [
                                'label' => 'Restore',
                                'action' => 'restore',
                                'variant' => 'secondary',
                                'icon' => 'refresh-cw',
                                'confirm' => true,
                            ],
                            'force_delete' => [
                                'label' => 'Force Delete',
                                'action' => 'force_delete',
                                'variant' => 'destructive',
                                'icon' => 'trash',
                                'confirm' => true,
                            ],
                        ],
                    ],
                ],
            ];
        }

        return ['columns' => ModelCompositionDefaults::guessTableColumns($attributes)];
    }

    private function formActionsDefinition(MakeCompositionInput $input, string $entityLabel): string
    {
        $actions = [];

        if ($input->includeBasicActions) {
            $actions['save'] = [
                'label' => 'Save',
                'action' => 'save',
                'variant' => 'secondary',
                'shortcut' => 'mod+s',
                'submit' => true,
                'success_message' => "{$entityLabel} saved",
            ];
            $actions['delete'] = [
                'label' => 'Delete',
                'action' => 'delete',
                'variant' => 'destructive',
                'icon' => 'trash',
                'confirm' => true,
                'visible_if' => [
                    'all' => [['form.mode_in' => ['edit']]],
                ],
            ];
        }

        return Yaml::dump(['actions' => $actions], PHP_INT_MAX, 2);
    }

    private function listActionsDefinition(MakeCompositionInput $input, string $entityLabel): string
    {
        $actions = [];

        if ($input->includeBasicActions) {
            $actions['create'] = [
                'label' => "Create {$entityLabel}",
                'action' => 'create',
                'variant' => 'primary',
                'icon' => 'plus',
            ];
        }

        return Yaml::dump(['actions' => $actions], PHP_INT_MAX, 2);
    }

    private function bulkActionsDefinition(MakeCompositionInput $input, string $entitiesLabel): string
    {
        $bulkActions = [];

        if ($input->includeBulkDelete) {
            $bulkActions['delete'] = [
                'label' => 'Delete',
                'action' => 'delete',
                'variant' => 'destructive',
                'icon' => 'trash',
                'confirm' => true,
                'success_message' => "{$entitiesLabel} deleted",
            ];
        }

        return Yaml::dump(['bulk_actions' => $bulkActions], PHP_INT_MAX, 2);
    }
}
