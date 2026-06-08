<?php

declare(strict_types=1);

namespace Flatpack\Services\Forms;

use Flatpack\Composition\EntityComposition;
use Flatpack\Composition\FormComposition;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Facades\Flatpack;
use Flatpack\Schema\Forms\FormFieldType;
use Flatpack\Schema\Forms\FormSchemaFields;
use Flatpack\Schema\Forms\FormSchemaNormalizer;
use Flatpack\Schema\RelationFieldQuery;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Resolves the related Eloquent model for relation-option endpoints and checks {@code viewAny}.
 */
final readonly class RelationOptionsAuthorizer
{
    public function __construct(
        private FlatpackAuthorizer $authorizer,
        private EntityComposition $entityComposition,
        private FormSchemaNormalizer $formSchemaNormalizer,
        private CompositionQuery $compositions,
        private WidgetSchemaNormalizer $widgetSchemaNormalizer,
    ) {}

    public function canViewRelatedOptions(?Authenticatable $user, string $relatedModelClass): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->authorizer->allows($user, 'viewAny', $relatedModelClass);
    }

    public function relatedModelClassForFormComboboxField(string $entity, string $fieldId): ?string
    {
        if ($fieldId === '') {
            return null;
        }

        $form = $this->entityComposition->formFor($entity);
        $schema = $this->normalizedFormSchemaArray($form, $this->entityComposition->formSchema($entity));
        $fieldDefinition = FormSchemaFields::fieldDefinitionById($schema, $fieldId);
        if ($fieldDefinition === null || ! FormFieldType::isRelationBackedCombobox($fieldDefinition)) {
            return null;
        }

        $parentClass = trim((string) ($form->model ?? ''));
        if ($parentClass === '') {
            return null;
        }

        return $this->relatedModelClass($parentClass, $fieldDefinition);
    }

    public function relatedModelClassForEmbeddedTableColumn(
        string $entity,
        string $tableFieldId,
        string $columnId,
    ): ?string {
        if ($tableFieldId === '' || $columnId === '') {
            return null;
        }

        $form = $this->entityComposition->formFor($entity);
        $schema = $this->normalizedFormSchemaArray($form, $this->entityComposition->formSchema($entity));
        $tableField = FormSchemaFields::fieldDefinitionById($schema, $tableFieldId);
        if ($tableField === null || trim((string) ($tableField['type'] ?? '')) !== 'table') {
            return null;
        }

        $column = FormSchemaFields::embeddedTableColumnById($tableField, $columnId);
        if ($column === null || trim((string) ($column['type'] ?? '')) !== 'relation') {
            return null;
        }

        $parentClass = trim((string) ($form->model ?? ''));
        if ($parentClass === '' || ! class_exists($parentClass) || ! is_subclass_of($parentClass, Model::class)) {
            return null;
        }

        $tableRelation = trim((string) ($tableField['relation'] ?? ''));
        if ($tableRelation === '' || ! method_exists($parentClass, $tableRelation)) {
            return null;
        }

        $parent = new $parentClass;
        $rel = $parent->{$tableRelation}();
        if (! $rel instanceof Relation) {
            return null;
        }

        $childClass = $rel->getRelated()::class;
        $fieldDefinition = $column;
        $fieldDefinition['type'] = 'combobox';

        return $this->relatedModelClass($childClass, $fieldDefinition);
    }

    public function relatedModelClassForDashboardWidgetColumn(string $widgetId, string $columnId): ?string
    {
        if ($widgetId === '' || $columnId === '') {
            return null;
        }

        /** @var array<string, mixed>|null $schema */
        $schema = $this->compositions->optional(Flatpack::dashboardEntity(), 'list');
        $normalized = $this->widgetSchemaNormalizer->normalize($schema);
        $definition = $normalized['widgets'][$widgetId] ?? null;
        if (! is_array($definition)) {
            return null;
        }

        $type = $definition['type'] ?? null;
        if ($type !== 'table' && $type !== 'grid') {
            return null;
        }

        $modelClass = is_string($definition['model'] ?? null)
            ? trim((string) $definition['model'])
            : '';
        if ($modelClass === '' || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        $column = $this->widgetColumnById($definition['columns'] ?? null, $columnId);
        if ($column === null || trim((string) ($column['type'] ?? '')) !== 'relation') {
            return null;
        }

        $fieldDefinition = $column;
        $fieldDefinition['type'] = 'combobox';

        return $this->relatedModelClass($modelClass, $fieldDefinition);
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return class-string<Model>|null
     */
    public function relatedModelClass(string $parentModelClass, array $fieldDefinition): ?string
    {
        $components = RelationFieldQuery::components($parentModelClass, $fieldDefinition);
        if ($components === null) {
            return null;
        }

        [$query] = $components;

        return $query->getModel()::class;
    }

    /**
     * @param  array<string, mixed>|null  $rawSchema
     * @return array<string, mixed>|null
     */
    private function normalizedFormSchemaArray(FormComposition $form, ?array $rawSchema): ?array
    {
        if ($rawSchema === null) {
            return null;
        }

        $modelClass = trim((string) ($form->model ?? ''));
        $normalized = $this->formSchemaNormalizer->normalizedFormSchema(
            $rawSchema,
            null,
            $modelClass !== '' ? $modelClass : null,
            null,
        );

        return $normalized?->toArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function widgetColumnById(mixed $columns, string $columnId): ?array
    {
        if (! is_array($columns) || $columns === []) {
            return null;
        }

        if (array_is_list($columns)) {
            foreach ($columns as $item) {
                if (! is_array($item)) {
                    continue;
                }
                if (trim((string) ($item['id'] ?? '')) === $columnId) {
                    return $item;
                }
            }

            return null;
        }

        $candidate = $columns[$columnId] ?? null;

        return is_array($candidate) ? $candidate : null;
    }
}
