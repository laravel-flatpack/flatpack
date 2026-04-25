/**
 * Toolbar row drawer for new rows: `action` `create` / `add` (`isEmbeddedTableCreateDraftToolbarAction`)
 * and `attach` (`isEmbeddedTableBelongsToManyAttachToolbarAction` + `attachExisting` body for optional
 * `renderRowDrawerAttachBody` on `DataTable`). Invariant: opening the draft does not call `onValueChange`;
 * parent updates on save via `useDataTableRowReplaceFlow` / `deferNotifyParentFormValues`.
 */
import * as React from 'react';
import {
    isEmbeddedTableBelongsToManyAttachToolbarAction,
    isEmbeddedTableCreateDraftToolbarAction,
} from '@/lib/data-table-action-semantics';
import type {
    DataTableRowDrawerBodyVariant,
    UseDataTableCreateRowFlowOptions,
    UseDataTableCreateRowFlowResult,
} from '@/types/data-table';

const NEW_ROW_ID_PREFIX = '__new__';

function blankTableRowFromColumns(
    columns: readonly { id: string; type?: string }[],
): Record<string, unknown> {
    const row: Record<string, unknown> = {};
    for (const column of columns) {
        const id = column.id.trim();
        if (id === '') {
            continue;
        }
        if (column.type === 'checkbox' || column.type === 'switch') {
            row[id] = false;
            continue;
        }
        row[id] = '';
    }

    return row;
}

export function useDataTableCreateRowFlow({
    rowDetailDrawer,
    toolbarActions,
    schemaColumns,
    rowIdentity,
    mutations,
}: UseDataTableCreateRowFlowOptions): UseDataTableCreateRowFlowResult {
    const { getStableRowId } = rowIdentity;
    const { data, onToolbarAction } = mutations;
    const [detailDrawerOpen, setDetailDrawerOpen] = React.useState(false);
    const [detailDrawerRowId, setDetailDrawerRowId] = React.useState<
        string | null
    >(null);
    const [detailDrawerDraftRow, setDetailDrawerDraftRow] =
        React.useState<Record<string, unknown> | null>(null);
    const [detailDrawerBodyVariant, setDetailDrawerBodyVariant] =
        React.useState<DataTableRowDrawerBodyVariant>('rowFields');

    const detailDrawerRow = React.useMemo(() => {
        if (!rowDetailDrawer || detailDrawerRowId == null) {
            return null;
        }
        if (detailDrawerRowId.startsWith(NEW_ROW_ID_PREFIX)) {
            return detailDrawerDraftRow;
        }
        const idx = data.findIndex(
            (row, index) => getStableRowId(row, index) === detailDrawerRowId,
        );
        return idx >= 0 ? data[idx] : null;
    }, [
        data,
        detailDrawerDraftRow,
        detailDrawerRowId,
        getStableRowId,
        rowDetailDrawer,
    ]);

    const resetDetailDrawer = React.useCallback(() => {
        setDetailDrawerOpen(false);
        setDetailDrawerRowId(null);
        setDetailDrawerDraftRow(null);
        setDetailDrawerBodyVariant('rowFields');
    }, []);

    React.useEffect(() => {
        if (detailDrawerOpen && detailDrawerRow == null) {
            resetDetailDrawer();
        }
    }, [detailDrawerOpen, detailDrawerRow, resetDetailDrawer]);

    const beginCreateRowInDrawer = React.useCallback(
        (actionId: string): boolean => {
            if (!rowDetailDrawer) {
                return false;
            }
            const actionDef = toolbarActions.find(
                (action) => action.id === actionId,
            );
            const actionKey = actionDef?.action;
            if (!isEmbeddedTableCreateDraftToolbarAction(actionKey)) {
                return false;
            }

            setDetailDrawerBodyVariant('rowFields');
            setDetailDrawerDraftRow(blankTableRowFromColumns(schemaColumns));
            setDetailDrawerRowId(`${NEW_ROW_ID_PREFIX}:${Date.now()}`);
            setDetailDrawerOpen(true);
            return true;
        },
        [rowDetailDrawer, schemaColumns, toolbarActions],
    );

    const beginAttachRowInDrawer = React.useCallback(
        (actionId: string): boolean => {
            if (!rowDetailDrawer) {
                return false;
            }
            const actionDef = toolbarActions.find(
                (action) => action.id === actionId,
            );
            const actionKey = actionDef?.action;
            if (!isEmbeddedTableBelongsToManyAttachToolbarAction(actionKey)) {
                return false;
            }
            setDetailDrawerBodyVariant('attachExisting');
            setDetailDrawerDraftRow(blankTableRowFromColumns(schemaColumns));
            setDetailDrawerRowId(`${NEW_ROW_ID_PREFIX}:${Date.now()}`);
            setDetailDrawerOpen(true);
            return true;
        },
        [rowDetailDrawer, schemaColumns, toolbarActions],
    );

    const handleToolbarActionClick = React.useCallback(
        (actionId: string) => {
            if (beginCreateRowInDrawer(actionId)) {
                return;
            }
            if (beginAttachRowInDrawer(actionId)) {
                return;
            }
            onToolbarAction?.(actionId);
        },
        [beginAttachRowInDrawer, beginCreateRowInDrawer, onToolbarAction],
    );

    const openDetailDrawerForRow = React.useCallback((rowId: string) => {
        setDetailDrawerBodyVariant('rowFields');
        setDetailDrawerRowId(rowId);
        setDetailDrawerOpen(true);
    }, []);

    const clearCreateDraftRow = React.useCallback(() => {
        setDetailDrawerDraftRow(null);
    }, []);

    const handleDetailDrawerOpenChange = React.useCallback(
        (open: boolean) => {
            if (!open) {
                resetDetailDrawer();
                return;
            }
            setDetailDrawerOpen(true);
        },
        [resetDetailDrawer],
    );

    return {
        newRowIdPrefix: NEW_ROW_ID_PREFIX,
        detailDrawerOpen,
        detailDrawerRowId,
        detailDrawerRow,
        detailDrawerBodyVariant,
        openDetailDrawerForRow,
        handleDetailDrawerOpenChange,
        clearCreateDraftRow,
        resetDetailDrawer,
        handleToolbarActionClick,
    };
}
