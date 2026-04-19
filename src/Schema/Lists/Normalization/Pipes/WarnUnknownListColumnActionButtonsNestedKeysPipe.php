<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists\Normalization\Pipes;

use Closure;
use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Schema\Lists\Normalization\ListSchemaPipelineState;
use Flatpack\Support\CompositionNestedKeyWarnings;

final class WarnUnknownListColumnActionButtonsNestedKeysPipe
{
    public function handle(ListSchemaPipelineState $state, Closure $next): mixed
    {
        if ($state->log === null) {
            return $next($state);
        }

        $columns = $state->schema['columns'] ?? null;
        if (! is_array($columns)) {
            return $next($state);
        }

        foreach ($columns as $columnKey => $columnDefinition) {
            if (! is_array($columnDefinition)) {
                continue;
            }

            $columnType = isset($columnDefinition['type'])
                ? trim((string) $columnDefinition['type'])
                : '';
            if ($columnType !== 'actions') {
                continue;
            }

            $columnId = trim((string) ($columnDefinition['id'] ?? $columnKey));
            if ($columnId === '') {
                $columnId = 'column';
            }

            $actions = $columnDefinition['actions'] ?? null;
            $blockName = sprintf('columns.%s.actions', $columnId);

            CompositionNestedKeyWarnings::forStringKeyedBlocks(
                $actions,
                CompositionSchemaKeys::LIST_COLUMN_ACTION_BUTTON_ENTRY_KEYS,
                $blockName,
                $state->log,
            );
        }

        return $next($state);
    }
}
