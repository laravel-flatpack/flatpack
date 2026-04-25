/**
 * Row save/append: commits draft into `data` and defers `onValueChange` (see `deferNotifyParentFormValues`).
 */
import * as React from 'react';
import { deferNotifyParentFormValues } from '@/lib/data-table-utils';
import type {
    UseDataTableRowReplaceFlowOptions,
    UseDataTableRowReplaceFlowResult,
} from '@/types/data-table';

export function useDataTableRowReplaceFlow({
    rowIdentity,
    mutations,
    clearCreateDraftRow,
}: UseDataTableRowReplaceFlowOptions): UseDataTableRowReplaceFlowResult {
    const { getStableRowId, newRowIdPrefix } = rowIdentity;
    const { setData, onValueChange, onRowUpdate } = mutations;
    const handleRowReplace = React.useCallback(
        (rowId: string, nextRow: Record<string, unknown>) => {
            let previousRow: Record<string, unknown> | null = null;
            setData((prev) => {
                const idx = prev.findIndex(
                    (row, index) => getStableRowId(row, index) === rowId,
                );
                if (idx !== -1) {
                    previousRow = prev[idx];
                    const nextRows = prev.map((row, index) =>
                        index === idx ? nextRow : row,
                    );
                    deferNotifyParentFormValues(onValueChange, nextRows);
                    return nextRows;
                }

                if (!rowId.startsWith(newRowIdPrefix)) {
                    return prev;
                }

                const nextRows = [...prev, nextRow];
                deferNotifyParentFormValues(onValueChange, nextRows);
                return nextRows;
            });
            if (rowId.startsWith(newRowIdPrefix)) {
                clearCreateDraftRow();
            }
            if (onRowUpdate == null) {
                return;
            }
            void Promise.resolve(
                onRowUpdate({
                    rowId,
                    row: nextRow,
                }),
            ).catch(() => {
                if (previousRow == null) {
                    return;
                }
                setData((prev) => {
                    const idx = prev.findIndex(
                        (row, index) => getStableRowId(row, index) === rowId,
                    );
                    if (idx === -1) {
                        return prev;
                    }
                    const resolvedPreviousRow = previousRow;
                    if (resolvedPreviousRow == null) {
                        return prev;
                    }
                    const reverted = prev.map((row, index) =>
                        index === idx ? resolvedPreviousRow : row,
                    );
                    deferNotifyParentFormValues(onValueChange, reverted);
                    return reverted;
                });
            });
        },
        [
            clearCreateDraftRow,
            getStableRowId,
            newRowIdPrefix,
            onRowUpdate,
            onValueChange,
            setData,
        ],
    );

    return { handleRowReplace };
}
