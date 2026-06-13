<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization\WidgetTypes;

use Flatpack\Composition\ModelClassEntitySlugResolver;
use Flatpack\Schema\Forms\Normalization\FieldSpanCanonicalizer;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Support\CompositionDebugLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared normalization for table- and grid-style widget YAML (model-backed vs provider-backed columns, bulk actions, etc.).
 */
final readonly class TableLikeWidgetNormalizer
{
    public function __construct(
        private ?ModelClassEntitySlugResolver $modelEntitySlugResolver = null,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>|null
     */
    public function normalizeTableWidgetConfig(array $definition, ?CompositionDebugLog $debug, string $widgetId): ?array
    {
        return $this->normalizeTableLikeWidgetConfig('table', $definition, $debug, $widgetId);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>|null
     */
    public function normalizeGridWidgetConfig(array $definition, ?CompositionDebugLog $debug, string $widgetId): ?array
    {
        $normalized = $this->normalizeTableLikeWidgetConfig('grid', $definition, $debug, $widgetId);
        if ($normalized === null) {
            return null;
        }

        $columnIds = isset($normalized['columns']) && is_array($normalized['columns'])
            ? array_map(static fn (mixed $key): string => (string) $key, array_keys($normalized['columns']))
            : [];
        $cardSlotMap = $this->normalizeGridCardSlotMap($definition['card'] ?? null, $columnIds, $debug, $widgetId);
        if ($cardSlotMap !== null) {
            $normalized['card'] = $cardSlotMap;
        }

        return $normalized;
    }

    /**
     * Normalizes column definitions returned from a provider-backed table widget (list or keyed map).
     *
     * @param  list<array<string, mixed>>|array<string, mixed>  $columns
     * @return array<string, mixed>
     */
    public function normalizeProviderResolvedTableColumns(mixed $columns): array
    {
        if (! is_array($columns) || $columns === []) {
            return [];
        }
        if (array_is_list($columns)) {
            $keyed = [];
            foreach ($columns as $column) {
                if (! is_array($column)) {
                    continue;
                }
                $id = trim((string) ($column['id'] ?? $column['key'] ?? ''));
                if ($id === '') {
                    continue;
                }
                $keyed[$id] = $column;
            }

            return $keyed === [] ? [] : $this->normalizeProviderBackedTableWidgetColumns($keyed);
        }

        return $this->normalizeProviderBackedTableWidgetColumns($columns);
    }

    /**
     * Normalize the optional `card:` slot map on a grid widget. Drops unknown column references with a debug entry.
     *
     * @param  list<string>  $columnIds
     * @return array<string, mixed>|null
     */
    public function normalizeGridCardSlotMap(mixed $raw, array $columnIds, ?CompositionDebugLog $debug, string $widgetId): ?array
    {
        if (! is_array($raw) || $raw === []) {
            return null;
        }

        $known = array_flip($columnIds);
        $normalized = [];

        foreach (['title', 'subtitle', 'image', 'footer_actions'] as $slot) {
            if (! isset($raw[$slot]) || ! is_string($raw[$slot])) {
                continue;
            }
            $columnId = trim($raw[$slot]);
            if ($columnId === '') {
                continue;
            }
            if ($columnIds !== [] && ! isset($known[$columnId])) {
                $debug?->add(sprintf('widgets.%s card.%s ignored: column "%s" is not defined.', $widgetId, $slot, $columnId));

                continue;
            }
            $normalized[$slot] = $columnId;
        }

        foreach (['badges', 'body'] as $slot) {
            if (! isset($raw[$slot]) || ! is_array($raw[$slot])) {
                continue;
            }
            $values = [];
            foreach ($raw[$slot] as $value) {
                if (! is_string($value)) {
                    continue;
                }
                $columnId = trim($value);
                if ($columnId === '') {
                    continue;
                }
                if ($columnIds !== [] && ! isset($known[$columnId])) {
                    $debug?->add(sprintf('widgets.%s card.%s entry ignored: column "%s" is not defined.', $widgetId, $slot, $columnId));

                    continue;
                }
                $values[] = $columnId;
            }
            if ($values !== []) {
                $normalized[$slot] = $values;
            }
        }

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @param  array<int|string, mixed>  $rawBulkActions
     * @return list<array{id: string, label: string, action: string, icon: string, variant: string, success_message?: string, confirm?: bool, success_redirect?: string}>
     */
    public function normalizeTableWidgetBulkActions(array $rawBulkActions): array
    {
        $out = [];
        foreach ($rawBulkActions as $key => $definition) {
            if (! is_array($definition)) {
                continue;
            }
            $label = isset($definition['label']) ? trim((string) $definition['label']) : '';
            $action = isset($definition['action']) ? trim((string) $definition['action']) : '';
            if ($label === '' || $action === '') {
                continue;
            }
            $icon = isset($definition['icon']) ? trim((string) $definition['icon']) : '';
            $id = is_string($key) && $key !== '' ? $key : (string) count($out);
            $entry = [
                'id' => $id,
                'label' => $label,
                'action' => $action,
                'icon' => $icon,
                'variant' => $this->normalizeActionVariant($definition['variant'] ?? null),
            ];
            if (($definition['confirm'] ?? null) === true) {
                $entry['confirm'] = true;
            }
            $successMessage = isset($definition['success_message']) ? trim((string) $definition['success_message']) : '';
            if ($successMessage !== '') {
                $entry['success_message'] = $successMessage;
            }
            $successRedirect = $definition['success_redirect'] ?? null;
            if (is_string($successRedirect) && trim($successRedirect) !== '') {
                $entry['success_redirect'] = trim($successRedirect);
            }
            $out[] = $entry;
        }

        return $out;
    }

    public function normalizeActionVariant(mixed $raw): string
    {
        if (! is_string($raw)) {
            return 'outline';
        }
        $variant = trim($raw);
        if ($variant === '' || $variant === 'primary') {
            return $variant === 'primary' ? 'default' : 'outline';
        }
        if (in_array($variant, CompositionSchemaKeys::BUTTON_VARIANT_UI_VALUES, true)) {
            return $variant;
        }

        return 'outline';
    }

    /**
     * Provider-backed widget rows are display-only, so inline editing is always disabled.
     *
     * @param  array<string, mixed>  $columns
     * @return array<string, mixed>
     */
    public function normalizeProviderBackedTableWidgetColumns(array $columns): array
    {
        $normalized = [];
        foreach ($columns as $columnId => $columnDefinition) {
            if (! is_array($columnDefinition)) {
                continue;
            }
            $normalized[$columnId] = array_merge($columnDefinition, ['editable' => false]);
        }

        return $normalized;
    }

    /**
     * Model-backed widget rows support row-drawer editing. Relation columns should expose
     * a combobox edit field shape so drawer rendering matches form-table behavior.
     *
     * @param  array<string, mixed>  $columns
     * @return array<string, mixed>
     */
    public function normalizeModelBackedTableWidgetColumns(array $columns): array
    {
        $normalized = [];
        foreach ($columns as $columnId => $columnDefinition) {
            if (! is_array($columnDefinition)) {
                continue;
            }

            $next = $columnDefinition;
            $type = isset($columnDefinition['type']) ? trim((string) $columnDefinition['type']) : '';
            $hasRelation =
                isset($columnDefinition['relation']) &&
                trim((string) $columnDefinition['relation']) !== '' &&
                isset($columnDefinition['relation_name']) &&
                trim((string) $columnDefinition['relation_name']) !== '' &&
                isset($columnDefinition['relation_value']) &&
                trim((string) $columnDefinition['relation_value']) !== '';

            if (
                $type === 'relation' &&
                $hasRelation
            ) {
                $columnLabel = isset($columnDefinition['label']) ? trim((string) $columnDefinition['label']) : '';
                if ($columnLabel === '') {
                    $columnLabel = (string) $columnId;
                }

                $rawEditFormField = $columnDefinition['edit_form_field'] ?? $columnDefinition['editFormField'] ?? null;
                $editFormFieldOverrides = is_array($rawEditFormField) ? $rawEditFormField : [];

                $next['edit_form_field'] = array_merge([
                    'type' => 'combobox',
                    'label' => $columnLabel,
                    'placeholder' => sprintf('Select the %s', mb_strtolower($columnLabel)),
                    'required' => false,
                ], $editFormFieldOverrides);

                unset($next['editFormField']);
            }

            $normalized[$columnId] = $next;
        }

        return $normalized;
    }

    public function normalizeOptionalWidgetLabel(mixed $rawLabel): ?string
    {
        $label = is_string($rawLabel) ? trim($rawLabel) : '';
        if ($label !== '') {
            return $label;
        }

        return null;
    }

    /**
     * Shared normalization for table- and grid-style widgets, which differ only in renderer.
     *
     * @param  'table'|'grid'  $outType
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>|null
     */
    private function normalizeTableLikeWidgetConfig(string $outType, array $definition, ?CompositionDebugLog $debug, string $widgetId): ?array
    {
        $provider = trim((string) ($definition['provider'] ?? ''));
        $model = trim((string) ($definition['model'] ?? ''));
        if (($provider === '' && $model === '') || ($provider !== '' && $model !== '')) {
            $debug?->add(sprintf('widgets.%s ignored: %s widget requires exactly one of provider or model.', $widgetId, $outType));

            return null;
        }
        if ($model !== '' && (! class_exists($model) || ! is_subclass_of($model, Model::class))) {
            $debug?->add(sprintf('widgets.%s ignored: model "%s" is not a valid Eloquent model.', $widgetId, $model));

            return null;
        }

        $columns = $definition['columns'] ?? null;
        if ($provider !== '') {
            $normalizedColumns = is_array($columns) && $columns !== []
                ? $this->normalizeProviderBackedTableWidgetColumns($columns)
                : null;
        } else {
            if (! is_array($columns) || $columns === []) {
                $debug?->add(sprintf('widgets.%s ignored: model-backed %s widget requires a non-empty columns map.', $widgetId, $outType));

                return null;
            }
            $normalizedColumns = $this->normalizeModelBackedTableWidgetColumns($columns);
        }

        $normalized = [
            'type' => $outType,
            'label' => $this->normalizeOptionalWidgetLabel($definition['label'] ?? null),
            'description' => isset($definition['description']) ? (string) $definition['description'] : null,
            'icon' => isset($definition['icon']) ? (string) $definition['icon'] : null,
            'showColumnsVisibility' => is_bool($definition['showColumnsVisibility'] ?? null)
                ? $definition['showColumnsVisibility']
                : false,
        ];
        if ($normalizedColumns !== null) {
            $normalized['columns'] = $normalizedColumns;
        }
        if ($provider !== '') {
            $normalized['provider'] = $provider;
        }
        if ($model !== '') {
            $normalized['model'] = $model;
        }
        if (isset($definition['filters']) && is_array($definition['filters'])) {
            $normalized['filters'] = $definition['filters'];
        }
        if (isset($definition['actions']) && is_array($definition['actions'])) {
            $normalized['actions'] = $definition['actions'];
        }
        $rawBulkActions = $definition['bulk_actions'] ?? $definition['bulkActions'] ?? null;
        if (is_array($rawBulkActions)) {
            $bulkActions = $this->normalizeTableWidgetBulkActions($rawBulkActions);
            if ($bulkActions !== []) {
                $normalized['bulk_actions'] = $bulkActions;
            }
        }
        if (isset($definition['empty_state']) && is_array($definition['empty_state'])) {
            $normalized['empty_state'] = $definition['empty_state'];
        }
        if (isset($definition['pagination']) && (is_array($definition['pagination']) || is_bool($definition['pagination']))) {
            $normalized['pagination'] = $definition['pagination'];
        }
        if ($model !== '' && isset($definition['paginate']) && is_numeric($definition['paginate'])) {
            $normalized['paginate'] = max(1, (int) $definition['paginate']);
        }
        $defaultSort = $definition['default_sort'] ?? null;
        if (is_array($defaultSort)) {
            $key = trim((string) ($defaultSort['key'] ?? ''));
            $direction = trim((string) ($defaultSort['direction'] ?? ''));
            if ($key !== '' && in_array($direction, ['asc', 'desc'], true)) {
                $normalized['default_sort'] = [
                    'key' => $key,
                    'direction' => $direction,
                ];
            }
        }

        if ($model !== '') {
            $explicitEntity = trim((string) ($definition['entity'] ?? $definition['list_entity'] ?? ''));
            if ($explicitEntity !== '') {
                $normalized['list_entity'] = $explicitEntity;
            } elseif ($this->modelEntitySlugResolver !== null) {
                $resolved = $this->modelEntitySlugResolver->firstEntitySlugForModel($model);
                if ($resolved !== null) {
                    $normalized['list_entity'] = $resolved;
                }
            }
        }

        FieldSpanCanonicalizer::mergeIntoIfPresent(
            $definition,
            $normalized,
            sprintf('widgets.%s', $widgetId),
            $debug,
        );

        return $normalized;
    }
}
